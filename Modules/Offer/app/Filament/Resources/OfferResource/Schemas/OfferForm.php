<?php

namespace Modules\Offer\Filament\Resources\OfferResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Auth\Models\User;
use Modules\Form\Filament\FieldComponent;
use Modules\Form\Support\Field;
use Modules\Location\Models\Address;
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
                        TextInput::make('title')
                            ->label(__('offer::offer.title_field'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label(__('offer::offer.description'))
                            ->maxLength(Field::TEXTAREA_MAX_LENGTH)
                            ->rows(4)
                            ->columnSpanFull(),
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
                    ->schema(array_map(fn (Field $field) => FieldComponent::make($field, 'details.'.$field->name), $domain->attributes())),
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
