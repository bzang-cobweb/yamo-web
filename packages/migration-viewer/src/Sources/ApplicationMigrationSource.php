<?php

namespace Bza\MigrationViewer\Sources;

use Bza\MigrationViewer\Contracts\MigrationSource;
use Bza\MigrationViewer\Data\MigrationFile;
use Bza\MigrationViewer\Origin;

/**
 * The files of the application's migrations directory (database/migrations).
 */
class ApplicationMigrationSource implements MigrationSource
{
    private readonly string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? database_path('migrations');
    }

    public function files(): iterable
    {
        foreach (MigrationFiles::in($this->directory) as $name => $path) {
            yield new MigrationFile($name, $path, Origin::Application);
        }
    }
}
