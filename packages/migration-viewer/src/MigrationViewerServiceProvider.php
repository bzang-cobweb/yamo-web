<?php

namespace Bza\MigrationViewer;

use Bza\MigrationViewer\Console\SyncMigrationTablesCommand;
use Bza\MigrationViewer\Generator\MigrationGenerator;
use Bza\MigrationViewer\Replay\BlueprintCollector;
use Bza\MigrationViewer\Replay\BlueprintReader;
use Bza\MigrationViewer\Replay\MigrationReplayer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

        $this->app->bind(MigrationGenerator::class, fn (Application $app): MigrationGenerator => new MigrationGenerator(
            directory: (string) $app['config']->get('migration-viewer.generator.path'),
            inspector: $app->make(MigrationInspector::class),
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
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // "migrate" runs the generated files too.
        if (($generated = $this->app['config']->get('migration-viewer.generator.path')) !== null) {
            $this->loadMigrationsFrom($generated);
        }

        if ($this->app['config']->get('migration-viewer.sync_after_migrate', true)) {
            Event::listen(MigrationsEnded::class, fn (MigrationsEnded $event) => $this->syncAfterMigrate($event));
        }

        if ($this->app->runningInConsole()) {
            $this->commands([SyncMigrationTablesCommand::class]);

            $this->publishes([__DIR__.'/../config/migration-viewer.php' => config_path('migration-viewer.php')], 'migration-viewer-config');
            $this->publishes([__DIR__.'/../resources/lang' => $this->app->langPath('vendor/migration-viewer')], 'migration-viewer-translations');
        }
    }

    /**
     * Keeps migration_tables in step with the schema after each "migrate" or
     * "migrate:rollback". Never makes the migration command fail.
     */
    private function syncAfterMigrate(MigrationsEnded $event): void
    {
        if (($event->options['pretend'] ?? false) || ! Schema::hasTable('migration_tables')) {
            return;
        }

        try {
            // The replays memoized before the migrations ran know nothing of them.
            $this->app->forgetInstance(MigrationInspector::class);
            $this->app->make(MigrationTableSynchronizer::class)->sync();
        } catch (Throwable $exception) {
            Log::warning('migration-viewer: the tables could not be synchronized after migrating.', ['exception' => $exception]);
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
