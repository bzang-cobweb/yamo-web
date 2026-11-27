<?php

namespace Bza\MediaManager;

use Bza\MediaManager\Contracts\GranteeResolver;
use Bza\MediaManager\Models\MediaFolder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the framework-agnostic core. It knows nothing of Filament: the
 * Filament adapter is enabled by adding MediaManagerPlugin to a panel.
 */
class MediaManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/media-manager.php', 'media-manager');

        $this->app->bind(GranteeResolver::class, fn (Application $app): GranteeResolver => $app->make($app['config']->get('media-manager.grantee_resolver')));

        // Scoped, not singleton: the folders and rights read are kept for one
        // request only, so long-running workers (Octane, queues) see changes.
        $this->app->scoped(MediaAccess::class);
        $this->app->scoped(MediaLibrary::class);
        $this->app->scoped(Grants::class);
        $this->app->scoped(LinkedMedia::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'media-manager');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // The folders' files get their own path generator; the other models keep theirs.
        if (($generator = $this->app['config']->get('media-manager.path_generator')) !== null) {
            $this->app['config']->set('media-library.custom_path_generators', [
                MediaFolder::class => $generator,
                ...$this->app['config']->get('media-library.custom_path_generators', []),
            ]);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/media-manager.php' => config_path('media-manager.php')], 'media-manager-config');
            $this->publishes([__DIR__.'/../resources/lang' => $this->app->langPath('vendor/media-manager')], 'media-manager-translations');
        }
    }
}
