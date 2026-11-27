<?php

namespace Bza\MigrationViewer\Sources;

/**
 * Lists the migration files of a directory the way Laravel's migrator does:
 * "*_*.php" files, not nested, keyed by their name without ".php".
 */
final class MigrationFiles
{
    /**
     * @return array<string, string> The absolute path of each file, keyed by the migration name.
     */
    public static function in(string $directory): array
    {
        $directory = realpath($directory);

        if ($directory === false || ! is_dir($directory)) {
            return [];
        }

        $files = [];

        foreach (glob($directory.DIRECTORY_SEPARATOR.'*_*.php') ?: [] as $path) {
            $files[basename($path, '.php')] = $path;
        }

        ksort($files);

        return $files;
    }
}
