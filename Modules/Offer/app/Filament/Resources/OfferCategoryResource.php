<?php

namespace Modules\Offer\Filament\Resources;

use App\Filament\Schemas\TranslatableTabs;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Offer\Filament\Resources\OfferCategoryResource\Pages\CreateOfferCategory;
use Modules\Offer\Filament\Resources\OfferCategoryResource\Pages\EditOfferCategory;
use Modules\Offer\Filament\Resources\OfferCategoryResource\Pages\ListOfferCategories;
use Modules\Offer\Models\OfferCategory;

/**
 * Catégories d'offres : nom traduit, identifiant unique, position d'affichage.
 */
class OfferCategoryResource extends AbstractOfferResource
{
    protected static ?string $model = OfferCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        // Traduit : un onglet par langue, chaque traduction obligatoire.
                        TranslatableTabs::make(fn (string $locale): array => [
                            TextInput::make("name.{$locale}")
                                ->label(__('offer::offer.category_resource.name'))
                                ->required()
                                ->maxLength(255),
                        ], 'name'),
                        TextInput::make('slug')
                            ->label(__('offer::offer.category_resource.slug'))
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('position')
                            ->label(__('offer::offer.category_resource.position'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label(__('offer::offer.category_resource.name'))
                    ->state(fn (OfferCategory $record): string => (string) $record->getTranslation('name', app()->getLocale())),
                TextColumn::make('slug')
                    ->label(__('offer::offer.category_resource.slug'))
                    ->searchable(),
                TextColumn::make('offers_count')
                    ->label(__('offer::offer.category_resource.offers_count'))
                    ->counts('offers'),
            ])
            ->recordActions(self::getActions())
            ->toolbarActions(self::getBulkActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfferCategories::route('/'),
            'create' => CreateOfferCategory::route('/create'),
            'edit' => EditOfferCategory::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('offer::offer.category_resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('offer::offer.category_resource.plural');
    }
}
