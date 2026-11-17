<?php

namespace Modules\Location\Filament\Resources\CountryResource\Schemas;

use App\Filament\Schemas\TranslatableTabs;
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
                // Traduit : un onglet par langue, chaque traduction obligatoire.
                TranslatableTabs::make(fn (string $locale): array => [
                    TextInput::make("name.{$locale}")
                        ->label(__('location::region.name'))
                        ->required()
                        ->maxLength(255),
                ], 'name'),
                Toggle::make('active')
                    ->label(__('location::region.active'))
                    ->default(true),
            ]);
    }
}
