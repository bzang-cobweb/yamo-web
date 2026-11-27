<?php

namespace Bza\ConfigViewer\Tests;

use Bza\ConfigViewer\Sources\ModuleConfigSource;
use Bza\ConfigViewer\Support\PhpFileFinder;

/**
 * A module source fed with a fixture module instead of nwidart/laravel-modules.
 */
class FakeModuleConfigSource extends ModuleConfigSource
{
    public function __construct()
    {
        parent::__construct(new PhpFileFinder);
    }

    protected function modules(): iterable
    {
        yield ['name' => 'Shop', 'key' => 'shop', 'config_path' => __DIR__.'/fixtures/Modules/Shop/config'];
    }
}
