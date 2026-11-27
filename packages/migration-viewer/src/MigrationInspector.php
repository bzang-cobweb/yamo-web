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

        /** @var array<string, array{columns: array<string, string>, changes: list<TableChange>, dropped: bool}> $tables */
        $tables = [];

        foreach ($this->replays() as $replay) {
            foreach ($replay->changes as $change) {
                if ($change->table === null) {
                    continue;
                }

                $table = $tables[$change->table] ?? ['columns' => [], 'changes' => [], 'dropped' => false];
                $table['changes'][] = $change;

                if ($change->operation === Operation::Create || $change->operation === Operation::Drop) {
                    $table['columns'] = [];
                    $table['dropped'] = $change->operation === Operation::Drop;
                }

                $table['columns'] = $this->applyColumns($table['columns'], $change->details);

                if ($change->operation === Operation::Rename && $change->renamedTo !== null) {
                    $tables[$change->renamedTo] = ['columns' => $table['columns'], 'changes' => $tables[$change->renamedTo]['changes'] ?? [], 'dropped' => false];
                    $table['columns'] = [];
                    $table['dropped'] = true;
                }

                $tables[$change->table] = $table;
            }
        }

        ksort($tables);

        $summaries = [];

        foreach ($tables as $name => $table) {
            $summaries[$name] = new TableSummary($name, $table['columns'], $table['changes'], $table['dropped']);
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
     * @param  array<string, string>  $columns
     * @param  list<ChangeDetail>  $details
     * @return array<string, string>
     */
    private function applyColumns(array $columns, array $details): array
    {
        foreach ($details as $detail) {
            switch ($detail->kind) {
                case ChangeKind::AddColumn:
                case ChangeKind::ChangeColumn:
                    $columns[$detail->subject] = $detail->definition;
                    break;
                case ChangeKind::DropColumn:
                    unset($columns[$detail->subject]);
                    break;
                case ChangeKind::RenameColumn:
                    $columns = $this->renameKey($columns, $detail->subject, (string) $detail->target);
                    break;
                default:
                    break;
            }
        }

        return $columns;
    }

    /**
     * Renames a key in place, keeping the column order.
     *
     * @param  array<string, string>  $columns
     * @return array<string, string>
     */
    private function renameKey(array $columns, string $from, string $to): array
    {
        if (! array_key_exists($from, $columns)) {
            return $columns;
        }

        $renamed = [];

        foreach ($columns as $name => $definition) {
            $renamed[$name === $from ? $to : $name] = $definition;
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
