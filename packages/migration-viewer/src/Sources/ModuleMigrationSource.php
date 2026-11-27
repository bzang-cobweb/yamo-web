<?php

namespace Bza\MigrationViewer\Sources;

use Bza\MigrationViewer\Contracts\MigrationSource;
use Bza\MigrationViewer\Data\MigrationFile;
use Bza\MigrationViewer\Origin;
use Nwidart\Modules\Facades\Module;

/**
 * The migration files of the enabled nwidart/laravel-modules modules.
 *
 * Yields nothing when the modules package is not installed.
 */
class ModuleMigrationSource implements MigrationSource
{
    public function files(): iterable
    {
        foreach ($this->modules() as $module) {
            foreach (MigrationFiles::in($module['migration_path']) as $name => $path) {
                yield new MigrationFile($name, $path, Origin::Module, $module['name']);
            }
        }
    }

    /**
     * The enabled modules. Override to support another modules package.
     *
     * @return iterable<array{name: string, migration_path: string}>
     */
    protected function modules(): iterable
    {
        if (! class_exists(Module::class)) {
            return;
        }

        $directory = (string) config('modules.paths.generator.migration.path', 'database/migrations');

        foreach (Module::allEnabled() as $module) {
            yield [
                'name' => $module->getName(),
                'migration_path' => $module->getExtraPath($directory),
            ];
        }
    }
}
