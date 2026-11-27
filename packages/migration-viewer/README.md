# bza/laravel-migration-viewer

Read-only viewer of a Laravel application's migration files, grouped by table.
The migrations of nwidart/laravel-modules modules and of packages are included.
A Filament adapter ships with it.

For each migration and each table it touches, the viewer shows:

- the operation: create, alter, rename, drop, or data (no schema change);
- each change, one per line:
  - `+ title string(100), nullable`
  - `− price`
  - `~ name → title`
  - `+ users_email_unique unique (email)`
  - `+ users_country_id_foreign (country_id) → countries(id), on delete set null`
- the origin of the file: application, module (with its name), or package;
- whether the migration has run.

Each table also gets a summary of its columns as the migrations leave it, or a mention that a later migration dropped it.

## How it works

The migration files are not parsed. Each migration's `up()` method is replayed the way `php artisan migrate --pretend` replays it:

- every configured connection is in *pretend* mode, so queries are recorded and never sent to the database;
- the schema builder resolves every `Blueprint` from the container, and a container hook keeps the blueprints created during the replay.

Each blueprint gives its columns and commands, which become the changes.

Replaying instead of parsing means that table names computed in code, such as `config('permission.table_names.roles')`, resolve correctly. It also has limits:

- checks such as `Schema::hasTable()` or `Schema::hasColumn()` see an empty database, so a branch guarded by one may be skipped or taken wrongly;
- code that does more than query the database still runs: files, cache, events, mails. Keep such code out of migrations;
- a migration that throws keeps what it recorded before the exception, and the page lists it as incomplete.

```
src/
├── MigrationInspector.php      Entry point: files, replays, changes by table, table summaries
├── Contracts/MigrationSource   Lists migration files with their origin
├── Sources/                    Application, Module (nwidart) and Registered (loadMigrationsFrom) sources
├── Replay/                     Pretend replay, blueprint collector and reader, column describer
├── Data/                       Immutable value objects (MigrationFile, TableChange, ChangeDetail, TableSummary…)
├── MigrationTableSynchronizer  Stores the table summaries in migration_tables
├── Models/MigrationTable       Eloquent model of migration_tables
├── Console/                    migration-viewer:sync
└── Filament/                   Thin adapter: plugin, migrations page, stored tables' resource
```

The core knows nothing of Filament. Another adapter, such as a Blade page, an Artisan command or a JSON endpoint, only needs `MigrationInspector`:

```php
use Bza\MigrationViewer\Data\MigrationQuery;
use Bza\MigrationViewer\MigrationInspector;

$inspector = app(MigrationInspector::class);

$inspector->changes(new MigrationQuery(table: 'users'));   // list<TableChange>, in run order
$inspector->table('users')?->columns;                      // ['id' => 'bigInteger, unsigned, auto-increment', …]
$inspector->failures();                                    // migrations whose replay threw
```

## Installation

```bash
composer require bza/laravel-migration-viewer
```

Add the page to a Filament panel:

```php
use Bza\MigrationViewer\Filament\MigrationViewerPlugin;

$panel->plugin(MigrationViewerPlugin::make()
    ->navigationGroup('Settings')
    ->navigationSort(120)
    ->authorize(fn (): bool => auth()->user()->isSuperAdmin()));
```

Every option accepts a closure. The page lists:

- filters: table, origin, module (shown only when the origin is « Module »), operation, status;
- a search on table, migration and column or index names.

A click on a row opens its table's detail page. Migrations that touch no table have no detail.

## Stored tables

The package's migration creates a `migration_tables` table with one row per table the migrations touch. The table name is unique. Each row holds:

- `dropped`;
- the current `columns` (name and definition);
- the `migrations` that touch the table (name, path, operation, origin, module, status, changes);
- `columns_count`, `migrations_count`, `pending_count`, `last_migration`, `synced_at`.

`MigrationTableSynchronizer::sync()` fills the table and removes rows for tables no migration touches any more. It runs:

- after each `migrate` or `migrate:rollback`, unless `sync_after_migrate` is false;
- with `php artisan migration-viewer:sync`;
- from the « Synchronize » button of the list page.

The Filament plugin also registers the `MigrationTableResource` resource. It is read-only and shares the plugin's `authorize()`:

- the list (`/migration-tables`) offers a search on the name and filters for dropped tables and tables with pending migrations;
- the detail page (`/migration-tables/{name}`) shows the table's state, its migration files with their operation, changes, origin and status, then its current columns.

The `migration_tables` model is `Bza\MigrationViewer\Models\MigrationTable`, addressed by its name in URLs.

## Configuration

```bash
php artisan vendor:publish --tag=migration-viewer-config
```

| Key | Default | Meaning |
| --- | --- | --- |
| `sources` | application, module, registered | Classes implementing `MigrationSource`; a file listed twice keeps the first source. |
| `connections` | `null` (all) | Connections put in pretend mode during a replay. |
| `sync_after_migrate` | `true` (`MIGRATION_VIEWER_SYNC_AFTER_MIGRATE`) | Refresh `migration_tables` after each migrate or rollback. |

Translations (en, fr, de) can be published with the `migration-viewer-translations` tag.

## Tests

The tests run inside the host application, on fixture migrations only:

```bash
php artisan test packages/migration-viewer/tests
```
