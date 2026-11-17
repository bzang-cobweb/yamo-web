<?php

namespace Modules\Location\Filament\Resources\CountryResource\Schemas;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Modules\Location\Filament\Forms\SingleCountry;
use Modules\Location\Models\Country;

/**
 * Formulaire d'une adresse dans le back-office. Dans la fiche d'un pays, le pays
 * est implicite ; dans celle d'un utilisateur, il se choisit. Les bornes sont
 * celles du formulaire `location.address` de l'app.
 */
abstract class AddressForm
{
    /**
     * @param  list<Component>  $before  champs placés avant la rue (le pays)
     * @param  Closure|null  $streetRule  règle de plus sur la rue (unicité), évaluée par Filament
     */
    public static function configure(Schema $schema, array $before = [], ?Closure $streetRule = null): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                ...$before,
                TextInput::make('street')
                    ->label(__('location::address.street'))
                    ->required()
                    ->maxLength(255)
                    ->when($streetRule !== null, fn (TextInput $input): TextInput => $input->rule($streetRule))
                    ->columnSpanFull(),
                TextInput::make('zip')
                    ->label(__('location::address.zip'))
                    ->required()
                    ->maxLength(20),
                TextInput::make('city')
                    ->label(__('location::address.city'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('latitude')
                    ->label(__('location::address.latitude'))
                    ->numeric()
                    ->minValue(-90)
                    ->maxValue(90),
                TextInput::make('longitude')
                    ->label(__('location::address.longitude'))
                    ->numeric()
                    ->minValue(-180)
                    ->maxValue(180),
            ]);
    }

    /**
     * Choix du pays (pays actifs), verrouillé s'il n'y en a qu'un.
     */
    public static function country(): Select
    {
        return SingleCountry::lock(Select::make('country_id')
            ->label(__('location::address.country'))
            ->options(fn (): array => Country::options())
            ->searchable()
            ->required());
    }
}
