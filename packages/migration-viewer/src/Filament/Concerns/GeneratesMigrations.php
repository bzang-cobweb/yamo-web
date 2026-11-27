<?php

namespace Bza\MigrationViewer\Filament\Concerns;

use Bza\MigrationViewer\Filament\MigrationViewerPlugin;
use Bza\MigrationViewer\MigrationInspector;
use Bza\MigrationViewer\MigrationTableSynchronizer;
use Closure;
use Filament\Notifications\Notification;
use InvalidArgumentException;

/**
 * Runs a generator call from a page: a refused spec becomes an error
 * notification, a written file refreshes migration_tables and is announced.
 */
trait GeneratesMigrations
{
    /**
     * @param  Closure(): string  $generate  Writes the file and returns its path.
     * @return bool Whether a file was written.
     */
    protected function generateMigration(Closure $generate): bool
    {
        try {
            $path = $generate();
        } catch (InvalidArgumentException $exception) {
            Notification::make()
                ->title(__('migration-viewer::migration-viewer.generator.refused'))
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return false;
        }

        // The replays memoized in this request do not know the new file.
        app()->forgetInstance(MigrationInspector::class);
        app(MigrationTableSynchronizer::class)->sync();

        Notification::make()
            ->title(__('migration-viewer::migration-viewer.generator.written'))
            ->body(str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).' — '.__('migration-viewer::migration-viewer.generator.run_migrate'))
            ->success()
            ->persistent()
            ->send();

        return true;
    }

    protected static function generatorEnabled(): bool
    {
        return (bool) config('migration-viewer.generator.enabled', false) && MigrationViewerPlugin::get()->isAuthorized();
    }
}
