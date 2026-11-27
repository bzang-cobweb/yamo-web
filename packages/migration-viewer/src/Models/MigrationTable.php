<?php

namespace Bza\MigrationViewer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A table as the migrations leave it, stored by MigrationTableSynchronizer.
 * Addressed by its name in URLs ("/migration-tables/users").
 *
 * @property int $id
 * @property string $name
 * @property bool $dropped
 * @property list<array{name: string, definition: string}> $columns
 * @property list<array{migration: string, path: string, operation: string, origin: string, module: ?string, status: string, details: list<string>}> $migrations
 * @property int $columns_count
 * @property int $migrations_count
 * @property int $pending_count
 * @property string|null $last_migration
 * @property Carbon|null $synced_at
 */
class MigrationTable extends Model
{
    protected $table = 'migration_tables';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'dropped' => 'boolean',
            'columns' => 'array',
            'migrations' => 'array',
            'columns_count' => 'integer',
            'migrations_count' => 'integer',
            'pending_count' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'name';
    }
}
