<?php

namespace Bza\MigrationViewer;

use Bza\MigrationViewer\Data\ChangeDetail;
use Bza\MigrationViewer\Data\TableChange;
use Bza\MigrationViewer\Data\TableSummary;
use Bza\MigrationViewer\Models\MigrationTable;
use Illuminate\Support\Facades\DB;

/**
 * Stores the inspector's table summaries in the migration_tables table: one
 * row per table, keyed by its unique name. Tables no migration touches any
 * more are removed.
 */
class MigrationTableSynchronizer
{
    public function __construct(private readonly MigrationInspector $inspector) {}

    /**
     * @return int The number of tables stored.
     */
    public function sync(): int
    {
        $tables = $this->inspector->tables();
        $now = now();

        DB::transaction(function () use ($tables, $now): void {
            foreach ($tables as $summary) {
                MigrationTable::query()->updateOrCreate(['name' => $summary->name], [...$this->attributes($summary), 'synced_at' => $now]);
            }

            MigrationTable::query()->whereNotIn('name', array_keys($tables))->delete();
        });

        return count($tables);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(TableSummary $summary): array
    {
        $migrations = array_map(fn (TableChange $change): array => [
            'migration' => $change->migration->name,
            'path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $change->migration->path),
            'operation' => $change->operation->value,
            'origin' => $change->migration->origin->value,
            'module' => $change->migration->module,
            'status' => match ($change->ran) {
                true => 'ran',
                false => 'pending',
                null => 'unknown',
            },
            'details' => array_map(fn (ChangeDetail $detail): string => (string) $detail, $change->details),
        ], $summary->changes);

        $columns = array_map(
            fn (string $name, string $definition): array => ['name' => $name, 'definition' => $definition],
            array_keys($summary->columns),
            array_values($summary->columns),
        );

        return [
            'dropped' => $summary->dropped,
            'columns' => $columns,
            'migrations' => $migrations,
            'columns_count' => count($columns),
            'migrations_count' => count($migrations),
            'pending_count' => count(array_filter($summary->changes, fn (TableChange $change): bool => $change->ran === false)),
            'last_migration' => $migrations === [] ? null : end($migrations)['migration'],
        ];
    }
}
