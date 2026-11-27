<?php

namespace Bza\ConfigViewer\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Lists the PHP files of a config directory, nested ones included.
 */
class PhpFileFinder
{
    /**
     * @return array<string, string> The absolute path of each file, keyed by its
     *                               path relative to the directory, without ".php"
     *                               and with dots as separators ("nested.file").
     */
    public function find(string $directory): array
    {
        $directory = realpath($directory);

        if ($directory === false || ! is_dir($directory)) {
            return [];
        }

        $files = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($directory) + 1, -strlen('.php'));
            $files[str_replace(DIRECTORY_SEPARATOR, '.', $relative)] = $file->getPathname();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }
}
