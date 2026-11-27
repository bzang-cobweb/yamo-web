<?php

namespace Bza\MigrationViewer\Generator;

use Bza\MigrationViewer\MigrationInspector;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Writes new migration files in the configured directory. Never runs them:
 * they show as pending until "php artisan migrate".
 */
class MigrationGenerator
{
    /**
     * @param  Closure(): DateTimeInterface|null  $clock
     */
    public function __construct(
        private readonly string $directory,
        private readonly MigrationInspector $inspector,
        private readonly MigrationCodeWriter $writer = new MigrationCodeWriter,
        private readonly ?Closure $clock = null,
    ) {}

    public function directory(): string
    {
        return $this->directory;
    }

    /**
     * @return string The path of the new file.
     */
    public function createTable(NewTable $table): string
    {
        if ($this->tableExists($table->name)) {
            throw new InvalidArgumentException("The table [{$table->name}] already exists.");
        }

        return $this->write("create_{$table->name}_table", $this->writer->create($table));
    }

    /**
     * @param  list<NewColumn>  $columns
     * @return string The path of the new file.
     */
    public function addColumns(string $table, array $columns): string
    {
        $summary = $this->inspector->table($table);

        if ($summary === null || $summary->dropped) {
            throw new InvalidArgumentException("The table [{$table}] does not exist.");
        }

        if ($columns === []) {
            throw new InvalidArgumentException('No column to add.');
        }

        $names = array_map(fn (NewColumn $column): string => $column->name, $columns);
        $existing = array_intersect($names, array_keys($summary->columns));

        if ($existing !== []) {
            throw new InvalidArgumentException("The table [{$table}] already has: ".implode(', ', $existing).'.');
        }

        if (count($names) !== count(array_unique($names))) {
            throw new InvalidArgumentException('The same column is listed twice.');
        }

        $suffix = count($names) <= 3 ? implode('_and_', $names) : 'columns';

        return $this->write("add_{$suffix}_to_{$table}_table", $this->writer->addColumns($table, $columns));
    }

    /**
     * Writes everything one edit changes on a table (rename, dropped, renamed,
     * changed and new columns) as a single migration.
     *
     * @param  list<ColumnEdit>  $edits  The wanted columns: every column missing from it is dropped.
     * @return string The path of the new file.
     */
    public function alterTable(string $table, string $newName, array $edits): string
    {
        return $this->write("alter_{$table}_table", $this->writer->alter($this->plan($table, $newName, $edits)));
    }

    /**
     * The columns of a table, as the edit form starts from them.
     *
     * @return array<string, ColumnState>
     */
    public function currentColumns(string $table): array
    {
        $summary = $this->inspector->table($table);

        return $summary === null || $summary->dropped ? [] : CurrentColumns::of($summary);
    }

    /**
     * Compares the wanted columns with the current ones.
     *
     * @param  list<ColumnEdit>  $edits
     */
    public function plan(string $table, string $newName, array $edits): TableAlteration
    {
        if (! $this->tableExists($table)) {
            throw new InvalidArgumentException("The table [{$table}] does not exist.");
        }

        if (preg_match(NewColumn::NAME_PATTERN, $newName) !== 1) {
            throw new InvalidArgumentException("Invalid table name [{$newName}].");
        }

        if ($newName !== $table && $this->tableExists($newName)) {
            throw new InvalidArgumentException("The table [{$newName}] already exists.");
        }

        $current = $this->currentColumns($table);
        $kept = [];
        $names = [];
        $renames = [];
        $changes = [];
        $adds = [];

        foreach ($edits as $edit) {
            if ($edit->original === null) {
                if ($edit->spec === null) {
                    throw new InvalidArgumentException('A new column needs a definition.');
                }

                $adds[] = $edit->spec;
                $names[] = $edit->spec->name;

                continue;
            }

            $state = $current[$edit->original] ?? throw new InvalidArgumentException("The table [{$table}] has no column [{$edit->original}].");

            if (isset($kept[$edit->original])) {
                throw new InvalidArgumentException("The column [{$edit->original}] is listed twice.");
            }

            $kept[$edit->original] = true;

            if ($edit->spec === null) {
                $names[] = $edit->original;

                continue;
            }

            if ($state->locked()) {
                throw new InvalidArgumentException("The column [{$edit->original}] can only be kept or dropped.");
            }

            $names[] = $edit->spec->name;

            if ($edit->spec->name !== $edit->original) {
                $renames[$edit->original] = $edit->spec->name;
            }

            if (! $edit->spec->sameDefinitionAs($state->spec)) {
                $changes[] = ['from' => $state, 'to' => $edit->spec];
            }
        }

        $duplicates = array_keys(array_filter(array_count_values($names), fn (int $count): bool => $count > 1));

        if ($duplicates !== []) {
            throw new InvalidArgumentException('Several columns are named '.implode(', ', $duplicates).'.');
        }

        $alteration = new TableAlteration(
            table: $table,
            newName: $newName,
            drops: array_values(array_filter($current, fn (ColumnState $state): bool => ! isset($kept[$state->name]))),
            renames: $renames,
            changes: $changes,
            adds: $adds,
        );

        if ($alteration->isEmpty()) {
            throw new InvalidArgumentException('Nothing changes.');
        }

        return $alteration;
    }

    /**
     * A table the migrations create and do not drop.
     */
    public function tableExists(string $table): bool
    {
        $summary = $this->inspector->table($table);

        return $summary !== null && ! $summary->dropped;
    }

    /**
     * A column of a table, as the migrations leave it.
     */
    public function columnExists(string $table, string $column): bool
    {
        return array_key_exists($column, $this->inspector->table($table)?->columns ?? []);
    }

    /**
     * The date in the name of the last migration, in run order.
     */
    private function latestMigrationTime(): ?DateTimeImmutable
    {
        $names = array_keys($this->inspector->files());
        $last = end($names);

        if ($last === false) {
            return null;
        }

        return DateTimeImmutable::createFromFormat('!Y_m_d_His', substr($last, 0, 17)) ?: null;
    }

    private function write(string $suffix, string $code): string
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0755, true) && ! is_dir($this->directory)) {
            throw new RuntimeException("Cannot create the directory [{$this->directory}].");
        }

        $time = DateTimeImmutable::createFromInterface($this->clock !== null ? ($this->clock)() : new DateTimeImmutable);
        $latest = $this->latestMigrationTime();

        // Never before an existing migration: migrations run in name order, so a
        // file dated earlier would run before, say, the creation of its table.
        if ($latest !== null && $latest >= $time) {
            $time = $latest->modify('+1 second');
        }

        // A second later if a file of the same second already has this name.
        do {
            $path = $this->directory.DIRECTORY_SEPARATOR.$time->format('Y_m_d_His').'_'.$suffix.'.php';
            $time = $time->modify('+1 second');
        } while (file_exists($path));

        if (file_put_contents($path, $code) === false) {
            throw new RuntimeException("Cannot write [{$path}].");
        }

        return $path;
    }
}
