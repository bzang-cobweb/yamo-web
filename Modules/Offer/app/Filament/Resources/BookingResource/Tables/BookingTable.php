<?php

namespace Modules\Offer\Filament\Resources\BookingResource\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Models\Booking;

/**
 * Les demandes, la plus récente en tête ; filtres par statut, type, offre et membre.
 */
abstract class BookingTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['offer', 'user']))
            ->columns([
                TextColumn::make('offer.title')
                    ->label(__('offer::booking.resource.offer'))
                    ->state(fn (Booking $record): string => (string) $record->offer->getTranslation('title', app()->getLocale()))
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label(__('offer::booking.resource.guest'))
                    ->state(fn (Booking $record): string => $record->user->getFilamentName())
                    ->searchable(['name', 'first_name', 'last_name', 'email']),
                TextColumn::make('type')
                    ->label(__('offer::booking.resource.type'))
                    ->formatStateUsing(fn (BookingTypeEnum $state): string => $state->label()),
                TextColumn::make('check_in')
                    ->label(__('offer::booking.resource.check_in'))
                    ->date()
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label(__('offer::booking.resource.check_out'))
                    ->date()
                    ->placeholder('—'),
                TextColumn::make('total')
                    ->label(__('offer::booking.resource.total'))
                    ->state(fn (Booking $record): ?string => $record->formattedTotal(app()->getLocale()))
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('offer::booking.resource.status'))
                    ->badge()
                    ->formatStateUsing(fn (BookingStatusEnum $state): string => $state->label())
                    ->color(fn (BookingStatusEnum $state): string => $state->color()),
                TextColumn::make('created_at')
                    ->label(__('offer::booking.resource.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('offer::booking.resource.status'))
                    ->options(BookingStatusEnum::options()),
                SelectFilter::make('type')
                    ->label(__('offer::booking.resource.type'))
                    ->options(BookingTypeEnum::options()),
                SelectFilter::make('offer_id')
                    ->label(__('offer::booking.resource.offer'))
                    ->relationship('offer', 'title')
                    ->getOptionLabelFromRecordUsing(fn ($offer): string => (string) $offer->getTranslation('title', app()->getLocale()))
                    ->searchable(),
                SelectFilter::make('user_id')
                    ->label(__('offer::booking.resource.guest'))
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($user): string => $user->getFilamentName())
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
