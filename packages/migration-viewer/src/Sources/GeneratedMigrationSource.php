<?php

namespace Bza\MigrationViewer\Sources;

use Bza\MigrationViewer\Contracts\MigrationSource;
use Bza\MigrationViewer\Data\MigrationFile;
use Bza\MigrationViewer\Origin;

/**
 * The files of the generator's directory (migration-viewer.generator.path).
 * Listed after the application and module sources: when the directory is one
 * of theirs, the files keep that origin.
 */
class GeneratedMigrationSource implements MigrationSource
{
    public function files(): iterable
    {
        $directory = (string) config('migration-viewer.generator.path', '');

        foreach ($directory !== '' ? MigrationFiles::in($directory) : [] as $name => $path) {
            yield new MigrationFile($name, $path, Origin::Generated);
        }
    }
}
