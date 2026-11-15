<?php

namespace App\Filament;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;

/**
 * Base des ressources Filament : jeux d'actions communs. Chaque action reste
 * autorisée par la policy du modèle.
 */
abstract class AbstractResource extends Resource
{
    /**
     * @return array<int, Action>
     */
    public static function getActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @return array<int, ActionGroup>
     */
    public static function getBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}
