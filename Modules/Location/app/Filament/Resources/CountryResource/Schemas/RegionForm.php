<?php

namespace Modules\Location\Filament\Resources\CountryResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Formulaire d'une région, dans la fiche de son pays (le pays est implicite).
 */
abstract class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Traduit : le sélecteur de langue de l'onglet choisit fr ou en.
                TextInput::make('name')
                    ->label(__('location::region.name'))
                    ->required()
                    ->maxLength(255),
                Toggle::make('active')
                    ->label(__('location::region.active'))
                    ->default(true),
            ]);
    }
}
