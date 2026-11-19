<?php

namespace Modules\Offer\Filament\Resources\BookingResource\InfoLists;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Models\Booking;

/**
 * Une demande : qui, quoi, quand, le message, puis la réponse.
 */
abstract class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('offer::booking.resource.singular'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('offer')
                            ->label(__('offer::booking.resource.offer'))
                            ->state(fn (Booking $record): string => (string) $record->offer->getTranslation('title', app()->getLocale())),
                        TextEntry::make('user')
                            ->label(__('offer::booking.resource.guest'))
                            ->state(fn (Booking $record): string => $record->user->getFilamentName()),
                        TextEntry::make('type')
                            ->label(__('offer::booking.resource.type'))
                            ->formatStateUsing(fn (BookingTypeEnum $state): string => $state->label()),
                        TextEntry::make('status')
                            ->label(__('offer::booking.resource.status'))
                            ->badge()
                            ->formatStateUsing(fn (BookingStatusEnum $state): string => $state->label())
                            ->color(fn (BookingStatusEnum $state): string => $state->color()),
                        TextEntry::make('check_in')->label(__('offer::booking.resource.check_in'))->date(),
                        TextEntry::make('check_out')->label(__('offer::booking.resource.check_out'))->date()->placeholder('—'),
                        TextEntry::make('guests')->label(__('offer::booking.resource.guests'))->placeholder('—'),
                        TextEntry::make('total')
                            ->label(__('offer::booking.resource.total'))
                            ->state(fn (Booking $record): ?string => $record->formattedTotal(app()->getLocale()))
                            ->placeholder('—'),
                        TextEntry::make('message')->label(__('offer::booking.resource.message'))->placeholder('—')->columnSpanFull(),
                        TextEntry::make('created_at')->label(__('offer::booking.resource.created_at'))->dateTime(),
                        TextEntry::make('answered_at')->label(__('offer::booking.resource.answered_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('refusal_message')->label(__('offer::booking.resource.refusal_message'))->placeholder('—'),
                        TextEntry::make('cancelled_at')->label(__('offer::booking.resource.cancelled_at'))->dateTime()->placeholder('—'),
                    ]),
            ]);
    }
}
