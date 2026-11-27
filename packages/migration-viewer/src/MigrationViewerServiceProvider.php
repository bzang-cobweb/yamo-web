<?php

namespace Bza\MigrationViewer;

use Bza\MigrationViewer\Replay\BlueprintCollector;
use Bza\MigrationViewer\Replay\BlueprintReader;
use Bza\MigrationViewer\Replay\MigrationReplayer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Registers the framework-agnostic core. It knows nothing of Filament: the
 * Filament adapter is enabled by adding MigrationViewerPlugin to a panel.
 */
class MigrationViewerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/migration-viewer.php', 'migration-viewer');

        $this->app->singleton(BlueprintCollector::class);

        $this->app->bind(MigrationReplayer::class, fn (Application $app): MigrationReplayer => new MigrationReplayer(
            migrator: $app->make('migrator'),
            db: $app->make('db'),
            collector: $app->make(BlueprintCollector::class),
            reader: $app->make(BlueprintReader::class),
            connections: $app['config']->get('migration-viewer.connections') ?? array_keys($app['config']->get('database.connections', [])),
        ));

        // Scoped, not singleton: the replays are memoized for one request only,
        // so long-running workers (Octane, queues) see new migrations.
        $this->app->scoped(MigrationInspector::class, fn (Application $app): MigrationInspector => new MigrationInspector(
            sources: array_map(fn (string $source) => $app->make($source), $app['config']->get('migration-viewer.sources', [])),
            replayer: $app->make(MigrationReplayer::class),
            ran: fn (): ?array => $this->ranMigrations($app->make('migrator')),
        ));
    }

    public function boot(): void
    {
        // The schema builder resolves each Blueprint from the container: this hook
        // sees them all, and the collector keeps them only while replaying.
        $this->app->resolving(Blueprint::class, function (Blueprint $blueprint, Application $app): void {
            $app->make(BlueprintCollector::class)->collect($blueprint);
        });

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'migration-viewer');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/migration-viewer.php' => config_path('migration-viewer.php')], 'migration-viewer-config');
            $this->publishes([__DIR__.'/../resources/lang' => $this->app->langPath('vendor/migration-viewer')], 'migration-viewer-translations');
        }
    }

    /**
     * @return list<string>|null Null when the migrations table cannot be read.
     */
    private function ranMigrations(Migrator $migrator): ?array
    {
        try {
            return $migrator->repositoryExists() ? $migrator->getRepository()->getRan() : [];
        } catch (Throwable) {
            return null;
        }
    }
}
