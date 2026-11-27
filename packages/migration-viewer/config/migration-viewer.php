<?php

use Bza\MigrationViewer\Sources\ApplicationMigrationSource;
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
        RegisteredMigrationSource::class,
    ],

    /*
    | The connections put in "pretend" mode while a migration is replayed: their
    | queries are recorded, never run. Null means every configured connection,
    | so that a migration writing through Schema::connection('other') is safe too.
    */
    'connections' => null,

];
