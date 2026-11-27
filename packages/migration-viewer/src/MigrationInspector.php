<?php

namespace Bza\MigrationViewer;

use Bza\MigrationViewer\Contracts\MigrationSource;
use Bza\MigrationViewer\Data\ChangeDetail;
use Bza\MigrationViewer\Data\MigrationFile;
use Bza\MigrationViewer\Data\MigrationQuery;
use Bza\MigrationViewer\Data\MigrationReplay;
use Bza\MigrationViewer\Data\TableChange;
use Bza\MigrationViewer\Data\TableSummary;
use Bza\MigrationViewer\Replay\MigrationReplayer;
use Closure;

/**
 * Entry point of the core: lists the migration files, replays them in run
 * order and answers adapters' questions. Knows nothing of any UI.
 */
class MigrationInspector
{
    /** @var list<MigrationReplay>|null */
    private ?array $replays = null;

    /** @var array<string, TableSummary>|null */
    private ?array $tables = null;

    /**
     * @param  iterable<MigrationSource>  $sources
     * @param  Closure(): (list<string>|null)|null  $ran  The migrations that have run, null when unknown.
     */
    public function __construct(
        private readonly iterable $sources,
        private readonly MigrationReplayer $replayer,
        private readonly ?Closure $ran = null,
    ) {}

    /**
     * Every migration file, in run order (by name), each listed once.
     *
     * @return array<string, MigrationFile>
     */
    public function files(): array
    {
        $files = [];
        $seen = [];

        foreach ($this->sources as $source) {
            foreach ($source->files() as $file) {
                $path = realpath($file->path) ?: $file->path;

                if (isset($seen[$path]) || isset($files[$file->name])) {
                    continue;
                }

                $seen[$path] = true;
                $files[$file->name] = $file;
            }
        }

        ksort($files);

        return $files;
    }

    /**
     * @return list<MigrationReplay>
     */
    public function replays(): array
    {
        if ($this->replays !== null) {
            return $this->replays;
        }

        $ran = $this->ran !== null ? ($this->ran)() : null;

        return $this->replays = array_map(
            fn (MigrationFile $file): MigrationReplay => $this->replayer->replay($file, $ran === null ? null : in_array($file->name, $ran, true)),
            array_values($this->files()),
        );
    }

    /**
     * The changes matching the query, grouped by table (alphabetically, changes
     * touching no table last), each table's changes in run order.
     *
     * @return list<TableChange>
     */
    public function changes(MigrationQuery $query = new MigrationQuery): array
    {
        $changes = [];

        foreach ($this->replays() as $replay) {
            foreach ($replay->changes as $change) {
                if ($this->matches($change, $query)) {
                    $changes[] = $change;
                }
            }
        }

        // The sort is stable: within a table, the run order is kept.
        usort($changes, fn (TableChange $a, TableChange $b): int => [$a->table === null, $a->table] <=> [$b->table === null, $b->table]);

        return $changes;
    }

    /**
     * Every table the migrations touch, as they leave it, by name.
     *
     * @return array<string, TableSummary>
     */
    public function tables(): array
    {
        if ($this->tables !== null) {
            return $this->tables;
        }

        /** @var array<string, array{columns: array<string, string>, attributes: array<string, array<string, mixed>>, indexes: array<string, array<string, mixed>>, foreignKeys: array<string, array<string, mixed>>, changes: list<TableChange>, dropped: bool}> $tables */
        $tables = [];
        $empty = ['columns' => [], 'attributes' => [], 'indexes' => [], 'foreignKeys' => [], 'changes' => [], 'dropped' => false];

        foreach ($this->replays() as $replay) {
            foreach ($replay->changes as $change) {
                if ($change->table === null) {
                    continue;
                }

                $table = $tables[$change->table] ?? $empty;
                $table['changes'][] = $change;

                if ($change->operation === Operation::Create || $change->operation === Operation::Drop) {
                    $table = [...$empty, 'changes' => $table['changes'], 'dropped' => $change->operation === Operation::Drop];
                }

                $table = $this->apply($table, $change->details);

                if ($change->operation === Operation::Rename && $change->renamedTo !== null) {
                    $tables[$change->renamedTo] = [...$table, 'changes' => $tables[$change->renamedTo]['changes'] ?? [], 'dropped' => false];
                    $table = [...$empty, 'changes' => $table['changes'], 'dropped' => true];
                }

                $tables[$change->table] = $table;
            }
        }

        ksort($tables);

        $summaries = [];

        foreach ($tables as $name => $table) {
            $summaries[$name] = new TableSummary(
                name: $name,
                columns: $table['columns'],
                changes: $table['changes'],
                dropped: $table['dropped'],
                columnAttributes: $table['attributes'],
                indexes: $table['indexes'],
                foreignKeys: $table['foreignKeys'],
            );
        }

        return $this->tables = $summaries;
    }

    public function table(string $name): ?TableSummary
    {
        return $this->tables()[$name] ?? null;
    }

    /**
     * The modules that have migrations, by name.
     *
     * @return list<string>
     */
    public function modules(): array
    {
        $modules = array_values(array_unique(array_filter(array_map(fn (MigrationFile $file): ?string => $file->module, $this->files()))));
        sort($modules);

        return $modules;
    }

    /**
     * The migrations whose replay threw: their changes may be incomplete.
     *
     * @return list<MigrationReplay>
     */
    public function failures(): array
    {
        return array_values(array_filter($this->replays(), fn (MigrationReplay $replay): bool => $replay->error !== null));
    }

    /**
     * Applies one change's details to a table's state.
     *
     * @param  array<string, mixed>  $table
     * @param  list<ChangeDetail>  $details
     * @return array<string, mixed>
     */
    private function apply(array $table, array $details): array
    {
        foreach ($details as $detail) {
            switch ($detail->kind) {
                case ChangeKind::AddColumn:
                case ChangeKind::ChangeColumn:
                    $table['columns'][$detail->subject] = $detail->definition;
                    $table['attributes'][$detail->subject] = $detail->attributes;
                    break;
                case ChangeKind::DropColumn:
                    unset($table['columns'][$detail->subject], $table['attributes'][$detail->subject]);
                    // The database drops the indexes and keys of a dropped column with it.
                    $table['indexes'] = array_filter($table['indexes'], fn (array $index): bool => ! in_array($detail->subject, $index['columns'], true));
                    $table['foreignKeys'] = array_filter($table['foreignKeys'], fn (array $key): bool => ! in_array($detail->subject, $key['columns'], true));
                    break;
                case ChangeKind::RenameColumn:
                    $to = (string) $detail->target;
                    $table['columns'] = $this->renameKey($table['columns'], $detail->subject, $to);
                    $table['attributes'] = $this->renameKey($table['attributes'], $detail->subject, $to);
                    $rename = fn (array $entry): array => [...$entry, 'columns' => array_map(fn (string $column): string => $column === $detail->subject ? $to : $column, $entry['columns'])];
                    $table['indexes'] = array_map($rename, $table['indexes']);
                    $table['foreignKeys'] = array_map($rename, $table['foreignKeys']);
                    break;
                case ChangeKind::AddIndex:
                    $table['indexes'][$detail->subject] = $detail->attributes;
                    break;
                case ChangeKind::DropIndex:
                    unset($table['indexes'][$detail->subject]);
                    break;
                case ChangeKind::AddForeign:
                    $table['foreignKeys'][$detail->subject] = $detail->attributes;
                    break;
                case ChangeKind::DropForeign:
                    unset($table['foreignKeys'][$detail->subject]);
                    break;
                default:
                    break;
            }
        }

        return $table;
    }

    /**
     * Renames a key in place, keeping the order.
     *
     * @template T
     *
     * @param  array<string, T>  $entries
     * @return array<string, T>
     */
    private function renameKey(array $entries, string $from, string $to): array
    {
        if (! array_key_exists($from, $entries)) {
            return $entries;
        }

        $renamed = [];

        foreach ($entries as $name => $entry) {
            $renamed[$name === $from ? $to : $name] = $entry;
        }

        return $renamed;
    }

    private function matches(TableChange $change, MigrationQuery $query): bool
    {
        if ($query->table !== null && $change->table !== $query->table) {
            return false;
        }

        if ($query->origin !== null && $change->migration->origin !== $query->origin) {
            return false;
        }

        if ($query->module !== null && $change->migration->module !== $query->module) {
            return false;
        }

        if ($query->operation !== null && $change->operation !== $query->operation) {
            return false;
        }

        if ($query->ran !== null && $change->ran !== $query->ran) {
            return false;
        }

        if ($query->search !== null && $query->search !== '' && ! $change->matches($query->search)) {
            return false;
        }

        return true;
    }
}
