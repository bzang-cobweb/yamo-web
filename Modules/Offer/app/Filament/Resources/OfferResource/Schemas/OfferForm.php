<?php

namespace Modules\Offer\Filament\Resources\OfferResource\Schemas;

use App\Filament\Schemas\TranslatableTabs;
use App\Support\Locales;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Auth\Models\User;
use Modules\Form\Filament\FieldComponent;
use Modules\Form\Support\Field;
use Modules\Location\Models\Address;
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
     * Les attributs du domaine ; un attribut traduisible a un onglet par langue.
     *
     * @return list<Component>
     */
    private static function attributes(Domain $domain): array
    {
        return array_map(fn (Field $field): Component => $field->isTranslatable()
            ? TranslatableTabs::make(fn (string $locale): array => [
                FieldComponent::make($field, "details.{$field->name}.{$locale}")
                    ->required($field->isRequired() && $locale === Locales::default()),
            ], "details-{$field->name}")->columnSpanFull()
            : FieldComponent::make($field, 'details.'.$field->name), $domain->attributes());
    }

    public static function configure(Schema $schema): Schema
    {
        $domain = app(DomainRegistry::class)->require();

        return $schema
            ->columns(2)
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('user_id')
                            ->label(__('offer::offer.owner'))
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn (User $user): string => $user->getFilamentName())
                            ->searchable(['name', 'first_name', 'last_name', 'email'])
                            ->required(),
                        Select::make('status')
                            ->label(__('offer::offer.status'))
                            ->options(OfferStatusEnum::options())
                            ->default(OfferStatusEnum::DRAFT->value)
                            ->required(),
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
                        Select::make('category_id')
                            ->label(__('offer::offer.category'))
                            ->options(fn (): array => OfferCategory::options()),
                        TextInput::make('price')
                            ->label($domain->priceLabel().' ('.$domain->currency().')')
                            ->numeric()
                            ->minValue(0)
                            ->required($domain->requiresPrice())
                            ->visible($domain->hasPrice()),
                        Select::make('address_id')
                            ->label(__('offer::offer.address'))
                            ->relationship('address', 'street')
                            ->getOptionLabelFromRecordUsing(fn (Address $address): string => $address->street.', '.$address->zip.' '.$address->city)
                            ->searchable(['street', 'zip', 'city'])
                            ->required($domain->requiresAddress())
                            ->visible($domain->hasAddress())
                            ->columnSpanFull(),
                    ]),
                Section::make(__('offer::offer.attributes'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema(self::attributes($domain)),
                Section::make(__('offer::offer.photos'))
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('photos')
                            ->hiddenLabel()
                            ->collection(Offer::PHOTOS)
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->maxFiles(20),
                    ]),
            ]);
    }
}
