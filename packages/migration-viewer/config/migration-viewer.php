<?php

use Bza\MigrationViewer\Sources\ApplicationMigrationSource;
use Bza\MigrationViewer\Sources\GeneratedMigrationSource;
use Bza\MigrationViewer\Sources\ModuleMigrationSource;
use Bza\MigrationViewer\Sources\RegisteredMigrationSource;

return [

    /*
    | Sources that list migration files. Each class implements
    | Bza\MigrationViewer\Contracts\MigrationSource and is resolved from the
    | container. A file listed by several sources is kept with the first one.
    */
    'sources' => [
        ApplicationMigrationSource::class,
        ModuleMigrationSource::class,
        GeneratedMigrationSource::class,
        RegisteredMigrationSource::class,
    ],

    /*
    | The connections put in "pretend" mode while a migration is replayed: their
    | queries are recorded, never run. Null means every configured connection,
    | so that a migration writing through Schema::connection('other') is safe too.
    */
    'connections' => null,

    /*
    | Refresh the migration_tables table after each "migrate" or "migrate:rollback".
    | Otherwise run "php artisan migration-viewer:sync" or use the page's button.
    */
    'sync_after_migrate' => env('MIGRATION_VIEWER_SYNC_AFTER_MIGRATE', true),

    /*
    | The generator writes new migration files ("Nouvelle table", "Ajouter des
    | champs") in this directory, never runs them. The directory is registered
    | on the migrator, so "php artisan migrate" runs its files, and listed by
    | GeneratedMigrationSource. Writing files from a web page is only enabled
    | in the local environment by default.
    */
    'generator' => [
        'enabled' => (bool) env('MIGRATION_VIEWER_GENERATOR_ENABLED', env('APP_ENV', 'production') === 'local'),
        'path' => env('MIGRATION_VIEWER_GENERATOR_PATH', database_path('migrations/generated')),
    ],

];
