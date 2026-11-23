<?php

namespace Modules\Offer\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Filament\Resources\OfferResource\Pages\CreateOffer;
use Modules\Offer\Filament\Resources\OfferResource\Pages\EditOffer;
use Modules\Offer\Filament\Resources\OfferResource\Pages\ListOffers;
use Modules\Offer\Filament\Resources\OfferResource\RelationManagers\BookingsRelationManager;
use Modules\Offer\Filament\Resources\OfferResource\RelationManagers\ReviewsRelationManager;
use Modules\Offer\Filament\Resources\OfferResource\Schemas\OfferForm;
use Modules\Offer\Filament\Resources\OfferResource\Tables\OfferTable;
use Modules\Offer\Models\Offer;

/**
 * Toutes les offres, avec les attributs du domaine actif et les photos.
 * Libellés : ceux du domaine (« Logement », « Logements »).
 */
class OfferResource extends AbstractOfferResource
{
    protected static ?string $model = Offer::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return OfferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OfferTable::configure($table);
    }

    /**
     * Les offres retirées (suppression douce) restent visibles, filtrables et restaurables.
     *
     * @return Builder<Offer>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Les avis de l'offre, en onglet de sa fiche (décision 2026-11-19-acces-avis-backoffice).
     */
    public static function getRelations(): array
    {
        return [
            // Les demandes, puis les avis (décision 2026-11-23-demandes-dans-l-offre).
            BookingsRelationManager::class,
            ReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOffers::route('/'),
            'create' => CreateOffer::route('/create'),
            'edit' => EditOffer::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return app(DomainRegistry::class)->active()?->label() ?? __('offer::offer.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return app(DomainRegistry::class)->active()?->pluralLabel() ?? __('offer::offer.plural');
    }
}
