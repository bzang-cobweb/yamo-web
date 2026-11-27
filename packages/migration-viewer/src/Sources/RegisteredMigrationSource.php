<?php

namespace Bza\MigrationViewer\Sources;

use Bza\MigrationViewer\Contracts\MigrationSource;
use Bza\MigrationViewer\Data\MigrationFile;
use Bza\MigrationViewer\Origin;
use Illuminate\Database\Migrations\Migrator;

/**
 * The directories registered on the migrator by service providers
 * (loadMigrationsFrom). Listed after the module source, so the module
 * directories registered this way keep their module.
 */
class RegisteredMigrationSource implements MigrationSource
{
    public function __construct(private readonly Migrator $migrator) {}

    public function files(): iterable
    {
        foreach ($this->migrator->paths() as $directory) {
            // A path may also be a single file.
            $files = is_file($directory) ? [basename($directory, '.php') => $directory] : MigrationFiles::in($directory);

            foreach ($files as $name => $path) {
                yield new MigrationFile($name, $path, Origin::Package);
            }
        }
    }
}
