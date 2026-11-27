<?php

namespace Bza\MigrationViewer\Tests;

use Bza\MigrationViewer\Sources\ModuleMigrationSource;

/**
 * A module source fed with a fixture module instead of nwidart/laravel-modules.
 */
class FakeModuleMigrationSource extends ModuleMigrationSource
{
    protected function modules(): iterable
    {
        yield ['name' => 'Shop', 'migration_path' => __DIR__.'/fixtures/Modules/Shop/database/migrations'];
    }
}
