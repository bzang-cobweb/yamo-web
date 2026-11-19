<?php

namespace Modules\Offer\Filament\Resources\OfferResource\Schemas;

use App\Filament\Schemas\TranslatableTabs;
use App\Support\Locales;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\Auth\Models\User;
use Modules\Form\Filament\FieldComponent;
use Modules\Form\Support\Field;
use Modules\Location\Filament\Forms\SingleCountry;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;

/**
 * Formulaire d'une offre dans le back-office : champs communs, attributs du
 * domaine actif (même schéma que l'app, dans `details`), photos.
 */
abstract class OfferForm
{
    /**
     * Les attributs du domaine d'un onglet : `listing` (« Annonce »), `access`
     * (« Accès ») ou `features` (« Caractéristiques », tous les autres) ; un
     * attribut traduisible a un onglet par langue.
     *
     * @return list<Component>
     */
    private static function attributes(Domain $domain, string $tab): array
    {
        $fields = array_values(array_filter($domain->inputAttributes(), fn (Field $field): bool => match (true) {
            in_array($field->name, $domain->listingAttributes(), true) => $tab === 'listing',
            in_array($field->name, $domain->accessAttributes(), true) => $tab === 'access',
            default => $tab === 'features',
        }));

        return array_map(fn (Field $field): Component => $field->isTranslatable()
            ? TranslatableTabs::make(fn (string $locale): array => [
                FieldComponent::make($field, "details.{$field->name}.{$locale}")
                    ->required($field->isRequired() && $locale === Locales::default()),
            ], "details-{$field->name}")->columnSpanFull()
            : FieldComponent::component($field, 'details.'.$field->name), $fields);
    }

    /**
     * L'onglet « Caractéristiques » : les attributs, avec le prix avant l'attribut
     * que désigne le domaine (« Loyer » avant « Charges mensuelles »), sinon à la fin.
     *
     * @return list<Component>
     */
    private static function features(Domain $domain): array
    {
        $components = self::attributes($domain, 'features');
        $price = TextInput::make('price')
            ->label($domain->priceLabel())
            ->helperText(__('offer::offer.price_currency_help'))
            ->numeric()
            ->minValue(0)
            ->required($domain->requiresPrice())
            ->visible($domain->hasPrice());
        $names = array_map(fn (Field $field): string => $field->name, array_values(array_filter(
            $domain->inputAttributes(),
            fn (Field $field): bool => ! in_array($field->name, [...$domain->listingAttributes(), ...$domain->accessAttributes()], true),
        )));
        $at = array_search($domain->priceBeforeAttribute(), $names, true);
        $at = $at === false ? count($components) : $at;

        return [...array_slice($components, 0, $at), $price, ...array_slice($components, $at)];
    }

    public static function configure(Schema $schema): Schema
    {
        $domain = app(DomainRegistry::class)->require();

        return $schema
            ->columns(2)
            ->components([
                // Des onglets (décision 2026-11-19-ombre-coeur-onglets-offre) : l'annonce, l'accès,
                // les caractéristiques du domaine, les photos ; les avis restent sous le formulaire.
                Tabs::make('offer')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make(__('offer::offer.tabs.listing'))
                            ->columns(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label(__('offer::offer.owner'))
                                    ->relationship('user', 'name')
                                    ->getOptionLabelFromRecordUsing(fn (User $user): string => $user->getFilamentName())
                                    ->searchable(['name', 'first_name', 'last_name', 'email'])
                                    ->required(),
                                // L'espace de l'offre (décision 2026-11-18-espace-pays) ; verrouillé s'il n'y a qu'un pays.
                                SingleCountry::lock(Select::make('country_id')
                                    ->label(__('offer::offer.country'))
                                    ->live()
                                    ->options(fn (): array => Country::options())
                                    ->searchable()
                                    ->required()),
                                // Traduits : un onglet par langue ; seule la langue par défaut est obligatoire.
                                TranslatableTabs::make(fn (string $locale): array => [
                                    TextInput::make("title.{$locale}")
                                        ->label(__('offer::offer.title_field'))
                                        ->required($locale === Locales::default())
                                        ->maxLength(255),
                                    Textarea::make("description.{$locale}")
                                        ->label(__('offer::offer.description'))
                                        ->maxLength(Field::TEXTAREA_MAX_LENGTH)
                                        ->rows(4),
                                ], 'texts')->columnSpanFull(),
                                // Les attributs de l'annonce (type, meublé), avant la catégorie.
                                ...self::attributes($domain, 'listing'),
                                Select::make('category_id')
                                    ->label(__('offer::offer.category'))
                                    ->options(fn (): array => OfferCategory::options()),
                                Select::make('address_id')
                                    ->label(__('offer::offer.address'))
                                    // Une adresse du pays de l'offre (décision 2026-11-18-espace-pays).
                                    ->relationship('address', 'street', fn ($query, Get $get) => $query->when(filled($get('country_id')), fn ($query) => $query->where('country_id', $get('country_id'))))
                                    ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        if (filled($value) && filled($get('country_id')) && (string) Address::query()->whereKey($value)->value('country_id') !== (string) $get('country_id')) {
                                            $fail(__('offer::offer.address_country'));
                                        }
                                    })
                                    ->getOptionLabelFromRecordUsing(fn (Address $address): string => $address->street.', '.$address->zip.' '.$address->city)
                                    ->searchable(['street', 'zip', 'city'])
                                    ->required($domain->requiresAddress())
                                    ->visible($domain->hasAddress())
                                    ->columnSpanFull(),
                            ]),
                        // L'accès à l'offre : statut, vedette, disponibilité, paiement (décision 2026-11-19-acces-avis-backoffice).
                        Tab::make(__('offer::offer.access'))
                            ->columns(2)
                            ->schema([
                                Select::make('status')
                                    ->label(__('offer::offer.status'))
                                    ->options(OfferStatusEnum::options())
                                    ->default(OfferStatusEnum::DRAFT->value)
                                    ->required(),
                                // En tête de la vitrine jusqu'à cette date (décision 2026-11-19-contrat-vedette-favoris).
                                DateTimePicker::make('featured_until')
                                    ->label(__('offer::offer.featured_until'))
                                    ->helperText(__('offer::offer.featured_help'))
                                    ->native(false)
                                    ->seconds(false),
                                ...self::attributes($domain, 'access'),
                                DateTimePicker::make('paid_until')
                                    ->label(__('offer::offer.paid_until'))
                                    ->helperText(__('offer::offer.paid_help'))
                                    ->native(false)
                                    ->seconds(false),
                            ]),
                        Tab::make(__('offer::offer.attributes'))
                            ->columns(2)
                            ->schema(self::features($domain)),
                        Tab::make(__('offer::offer.photos'))
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('photos')
                                    ->hiddenLabel()
                                    ->collection(Offer::PHOTOS)
                                    ->multiple()
                                    ->reorderable()
                                    ->image()
                                    ->maxFiles(20),
                            ]),
                    ]),
            ]);
    }
}
