<?php

namespace Modules\Location\Filament\Resources\CountryResource\Schemas;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Modules\Location\Models\Address;
use Modules\Location\Models\Region;
use Modules\Location\Services\AddressService;

/**
 * Formulaire d'une adresse dans le back-office. Elle n'a pas de pays : sa région le donne (décision
 * 2026-11-25-adresse-sans-pays). Dans la fiche d'un pays, ses régions sont proposées ; dans celle d'un utilisateur,
 * celles de tous les pays actifs. Les bornes sont
 * celles du formulaire `auth.address` de l'app ; latitude, longitude et
 * suggestions de rue n'existent qu'ici (décision 2026-11-18-adresse-app-simplifiee).
 */
abstract class AddressForm
{
    /**
     * @param  list<Component>  $before  champs placés avant la rue (le pays)
     * @param  Closure|null  $streetRule  règle de plus sur la rue (unicité), évaluée par Filament
     * @param  bool  $suggestions  la rue propose les adresses existantes ; en choisir une remplit les champs de l'adresse
     * @param  list<Component>  $after  champs placés après les coordonnées (les indications d'un compte)
     * @param  int|null  $country  le pays des régions proposées (la fiche d'un pays) ; `null` : celles de tous les pays actifs
     */
    public static function configure(Schema $schema, array $before = [], ?Closure $streetRule = null, bool $suggestions = false, array $after = [], ?int $country = null): Schema
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
                // La région, après la ville, à la place du pays : elle le donne (décisions 2026-11-25-region-de-l-adresse et
                // 2026-11-25-adresse-sans-pays).
                self::region($country),
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
                ...$after,
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
            ->datalist(fn (Get $get, ?string $state): array => mb_strlen(trim((string) $state)) < AddressService::SUGGESTION_MIN_LENGTH
                ? []
                : app(AddressService::class)->suggest((string) $state, null, 10)
                    ->map(fn (Address $address): string => $address->suggestionLabel())
                    ->all())
            ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                if (! str_contains((string) $state, ' — ')) {
                    return;
                }

                $address = app(AddressService::class)->fromSuggestionLabel((string) $state, null);

                if ($address === null) {
                    return;
                }

                $set('region_id', (string) $address->region_id);
                $set('street', $address->street);
                $set('zip', $address->zip);
                $set('city', $address->city);
                $set('latitude', $address->latitude);
                $set('longitude', $address->longitude);
            });
    }

    /**
     * La région, cherchable, obligatoire : celles du pays, ou de tous les pays actifs avec le nom du pays (« Littoral
     * (Cameroun) ») (décision 2026-11-25-adresse-sans-pays).
     */
    public static function region(?int $country = null): Select
    {
        return Select::make('region_id')
            ->label(__('location::address.region'))
            ->options(fn (): array => $country === null ? Region::optionsWithCountry() : Region::optionsIn($country))
            ->searchable()
            ->required();
    }
}
