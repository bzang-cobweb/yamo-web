<?php

namespace Modules\Offer\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Offer\Filament\Resources\BookingResource\InfoLists\BookingInfolist;
use Modules\Offer\Filament\Resources\BookingResource\Pages\ListBookings;
use Modules\Offer\Filament\Resources\BookingResource\Pages\ViewBooking;
use Modules\Offer\Filament\Resources\BookingResource\Tables\BookingTable;
use Modules\Offer\Models\Booking;

/**
 * Les demandes de réservation et de visite, en lecture seule (décision
 * 2026-11-19-contrat-reservations) : la décision reste au propriétaire, dans l'app.
 */
class BookingResource extends AbstractOfferResource
{
    protected static ?string $model = Booking::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 20;

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'view' => ViewBooking::route('/{record}'),
        ];
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
