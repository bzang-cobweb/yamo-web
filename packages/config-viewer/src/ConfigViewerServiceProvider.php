<?php

namespace Bza\ConfigViewer;

use Bza\ConfigViewer\Contracts\ValueMasker;
use Bza\ConfigViewer\Support\ConfigFlattener;
use Bza\ConfigViewer\Support\KeyPatternMasker;
use Bza\ConfigViewer\Support\ValueFormatter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the framework-agnostic core. It knows nothing of Filament: the
 * Filament adapter is enabled by adding ConfigViewerPlugin to a panel.
 */
class ConfigViewerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config-viewer.php', 'config-viewer');

        $this->app->singleton(ValueMasker::class, fn (Application $app): ValueMasker => new KeyPatternMasker(
            segmentPatterns: $app['config']->get('config-viewer.mask.segments', []),
            keyPatterns: $app['config']->get('config-viewer.mask.keys', []),
            placeholder: $app['config']->get('config-viewer.mask.placeholder', '••••••••'),
        ));

        // Scoped, not singleton: the groups are memoized for one request only, so
        // long-running workers (Octane, queues) see configuration changes.
        $this->app->scoped(ConfigInspector::class, fn (Application $app): ConfigInspector => new ConfigInspector(
            config: $app['config'],
            sources: array_map(fn (string $source) => $app->make($source), $app['config']->get('config-viewer.sources', [])),
            masker: $app->make(ValueMasker::class),
            flattener: $app->make(ConfigFlattener::class),
            formatter: $app->make(ValueFormatter::class),
            hidden: $app['config']->get('config-viewer.hidden', []),
        ));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'config-viewer');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/config-viewer.php' => config_path('config-viewer.php')], 'config-viewer-config');
            $this->publishes([__DIR__.'/../resources/lang' => $this->app->langPath('vendor/config-viewer')], 'config-viewer-translations');
        }
    }
}
