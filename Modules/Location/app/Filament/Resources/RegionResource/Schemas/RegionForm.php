<?php

namespace Modules\Location\Filament\Resources\RegionResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Location\Models\Country;

abstract class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                static::getGeneralSection(),
            ]);
    }

    public static function getGeneralSection(): Section
    {
        return Section::make(__('location::region.sections.general'))
            ->columns(2)
            ->schema([
                Select::make('country_id')
                    ->label(__('location::region.country'))
                    ->options(fn (): array => static::countryOptions())
                    ->searchable()
                    ->required(),
                // Traduit : le sélecteur de langue de la page choisit fr ou en.
                TextInput::make('name')
                    ->label(__('location::region.name'))
                    ->required()
                    ->maxLength(255),
                Toggle::make('active')
                    ->label(__('location::region.active'))
                    ->default(true),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function countryOptions(): array
    {
        return Country::query()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Country $country): array => [$country->getKey() => $country->label()])
            ->sort()
            ->all();
    }
}
