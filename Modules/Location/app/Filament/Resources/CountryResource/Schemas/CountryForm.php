<?php

namespace Modules\Location\Filament\Resources\CountryResource\Schemas;

use App\Filament\Schemas\TranslatableTabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

abstract class CountryForm
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
        return Section::make(__('location::country.sections.general'))
            ->columns(2)
            ->schema([
                TextInput::make('code')
                    ->label(__('location::country.code'))
                    ->helperText(__('location::country.help.code'))
                    ->required()
                    ->length(2)
                    ->regex('/^[A-Za-z]{2}$/')
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state === null ? null : strtoupper($state)),
                // Traduit : un onglet par langue, chaque traduction obligatoire.
                TranslatableTabs::make(fn (string $locale): array => [
                    TextInput::make("name.{$locale}")
                        ->label(__('location::country.name'))
                        ->required()
                        ->maxLength(255),
                ], 'name'),
                TextInput::make('native_name')
                    ->label(__('location::country.native_name'))
                    ->helperText(__('location::country.help.native_name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('dial_code')
                    ->label(__('location::country.dial_code'))
                    ->helperText(__('location::country.help.dial_code'))
                    ->required()
                    ->regex('/^\+\d{1,4}$/')
                    ->maxLength(8),
                Toggle::make('active')
                    ->label(__('location::country.active'))
                    ->default(true),
            ]);
    }
}
