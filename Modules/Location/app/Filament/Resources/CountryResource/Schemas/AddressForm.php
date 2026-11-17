<?php

namespace Modules\Location\Filament\Resources\CountryResource\Schemas;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Modules\Auth\Enums\GenderEnum;
use Modules\Location\Filament\Forms\SingleCountry;
use Modules\Location\Http\Controllers\Api\AddressSuggestionController;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Services\AddressService;

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
     * @param  bool  $suggestions  la rue propose les adresses existantes ; en choisir une remplit les champs de l'adresse
     */
    public static function configure(Schema $schema, array $before = [], ?Closure $streetRule = null, bool $suggestions = false): Schema
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
                    ->when($suggestions, fn (TextInput $input): TextInput => self::withSuggestions($input))
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
     * Rue avec suggestions (décision 2026-11-18-adresse-du-compte-modele) : à
     * partir de 3 caractères, les adresses existantes du pays choisi (de tous les
     * pays sinon) sont proposées ; en choisir une remplit pays, rue, NPA, ville,
     * latitude et longitude.
     */
    private static function withSuggestions(TextInput $input): TextInput
    {
        return $input
            ->live(debounce: 400)
            ->autocomplete(false)
            ->helperText(__('location::address.suggestion_help'))
            ->datalist(fn (Get $get, ?string $state): array => mb_strlen(trim((string) $state)) < AddressSuggestionController::MIN_LENGTH
                ? []
                : app(AddressService::class)->suggest((string) $state, $get('country_id'), 10)
                    ->map(fn (Address $address): string => $address->suggestionLabel())
                    ->all())
            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                if (! str_contains((string) $state, ' — ')) {
                    return;
                }

                $address = app(AddressService::class)->fromSuggestionLabel((string) $state, $get('country_id'));

                if ($address === null) {
                    return;
                }

                $set('country_id', (string) $address->country_id);
                $set('street', $address->street);
                $set('zip', $address->zip);
                $set('city', $address->city);
                $set('latitude', $address->latitude);
                $set('longitude', $address->longitude);
            });
    }

    /**
     * Genre, prénom et nom du destinataire d'une adresse de compte.
     *
     * @return list<Component>
     */
    public static function recipient(): array
    {
        return [
            Select::make('gender')
                ->label(__('auth::user.gender'))
                ->options(GenderEnum::options())
                ->required()
                ->columnSpanFull(),
            TextInput::make('first_name')
                ->label(__('auth::user.first_name'))
                ->required()
                ->maxLength(255),
            TextInput::make('last_name')
                ->label(__('auth::user.last_name'))
                ->required()
                ->maxLength(255),
        ];
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
