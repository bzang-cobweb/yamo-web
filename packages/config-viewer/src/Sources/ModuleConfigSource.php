<?php

namespace Bza\ConfigViewer\Sources;

use Bza\ConfigViewer\Contracts\ConfigSource;
use Bza\ConfigViewer\Data\ConfigFile;
use Bza\ConfigViewer\Origin;
use Bza\ConfigViewer\Support\PhpFileFinder;
use Nwidart\Modules\Facades\Module;

/**
 * The config files of the enabled nwidart/laravel-modules modules, keyed the
 * way the module service provider merges them: "config.php" under the module's
 * lower name ("partner"), any other file under "<module>.<file>", with adjacent
 * duplicated segments removed ("partner/partner.php" stays "partner").
 *
 * Yields nothing when the modules package is not installed.
 */
class ModuleConfigSource implements ConfigSource
{
    public function __construct(private readonly PhpFileFinder $finder) {}

    public function files(): iterable
    {
        foreach ($this->modules() as $module) {
            foreach ($this->finder->find($module['config_path']) as $relativeKey => $path) {
                yield new ConfigFile($this->keyFor($module['key'], $relativeKey), $path, Origin::Module, $module['name']);
            }
        }
    }

    /**
     * The enabled modules. Override to support another modules package.
     *
     * @return iterable<array{name: string, key: string, config_path: string}>
     */
    protected function modules(): iterable
    {
        if (! class_exists(Module::class)) {
            return;
        }

        $configDirectory = (string) config('modules.paths.generator.config.path', 'config');

        foreach (Module::allEnabled() as $module) {
            yield [
                'name' => $module->getName(),
                'key' => $module->getLowerName(),
                'config_path' => $module->getExtraPath($configDirectory),
            ];
        }
    }

    private function keyFor(string $moduleKey, string $relativeKey): string
    {
        if ($relativeKey === 'config') {
            return $moduleKey;
        }

        $segments = [];

        foreach (explode('.', $moduleKey.'.'.$relativeKey) as $segment) {
            if (end($segments) !== $segment) {
                $segments[] = $segment;
            }
        }

        return implode('.', $segments);
    }
}
