<?php

namespace Modules\Offer\Filament\Resources\OfferResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Offer\Filament\Resources\BookingResource;
use Modules\Offer\Filament\Resources\BookingResource\Tables\BookingTable;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * Les demandes d'une offre, sur sa fiche du back-office (décision 2026-11-23-demandes-dans-l-offre) : la table
 * de la ressource « Demandes », sans la colonne ni le filtre « Offre » ; en lecture seule, « Voir » ouvre la
 * fiche de la demande (`BookingPolicy`).
 */
class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    public function table(Table $table): Table
    {
        return BookingTable::configure($table, withOffer: false)
            ->recordTitleAttribute('id')
            ->recordActions([
                ViewAction::make()->url(fn (Booking $record): string => BookingResource::getUrl('view', ['record' => $record])),
            ]);
    }

    /**
     * « Demandes (12) ».
     */
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        /** @var Offer $ownerRecord */
        return __('offer::booking.resource.plural').' ('.$ownerRecord->bookings()->count().')';
    }

    public static function getModelLabel(): string
    {
        return __('offer::booking.resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('offer::booking.resource.plural');
    }
}
