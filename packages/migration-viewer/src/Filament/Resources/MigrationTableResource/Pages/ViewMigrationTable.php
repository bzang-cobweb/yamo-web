<?php

namespace Bza\MigrationViewer\Filament\Resources\MigrationTableResource\Pages;

use Bza\MigrationViewer\Filament\Resources\MigrationTableResource;
use Bza\MigrationViewer\Models\MigrationTable;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property MigrationTable $record
 */
class ViewMigrationTable extends ViewRecord
{
    private const KEY = 'migration-viewer::migration-viewer.generator.';

    protected static string $resource = MigrationTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label(__(self::KEY.'edit'))
                ->icon(Heroicon::OutlinedPencilSquare)
                ->visible(fn (): bool => EditMigrationTable::canAccess(['record' => $this->record]))
                ->url(fn (): string => MigrationTableResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
