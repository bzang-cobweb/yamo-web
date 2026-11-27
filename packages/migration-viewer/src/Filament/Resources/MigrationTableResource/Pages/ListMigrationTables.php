<?php

namespace Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages;

use Bza\MigrationViewer\Filament\Resources\MigrationTableResource;
use Bza\MigrationViewer\MigrationTableSynchronizer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMigrationTables extends ListRecords
{
    protected static string $resource = MigrationTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label(__('migration-viewer::migration-viewer.tables.sync'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (MigrationTableSynchronizer $synchronizer): void {
                    $count = $synchronizer->sync();

                    Notification::make()
                        ->title(trans_choice('migration-viewer::migration-viewer.tables.synced', $count, ['count' => $count]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
