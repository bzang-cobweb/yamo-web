<?php

namespace Modules\Offer\Filament\Resources\OfferResource\Tables;

use App\Support\ContainsSearch;
use App\Support\Locales;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Auth\Models\User;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Filament\Resources\OfferResource;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;

abstract class OfferTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            // Propriétaire et chemin de la catégorie sans requête par ligne.
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'category.parent.parent']))
            ->columns([
                TextColumn::make('title')
                    ->label(__('offer::offer.title_field'))
                    ->state(fn (Offer $record): string => (string) $record->getTranslation('title', app()->getLocale()))
                    // Toutes langues confondues.
                    ->searchable(query: fn ($query, string $search) => $query->where(fn ($query) => ContainsSearch::orWhere($query, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $search))),
                TextColumn::make('user.name')
                    ->label(__('offer::offer.owner'))
                    ->state(fn (Offer $record): string => (string) $record->user?->getFilamentName())
                    ->searchable(['name', 'first_name', 'last_name']),
                TextColumn::make('status')
                    ->label(__('offer::offer.status'))
                    ->badge()
                    ->formatStateUsing(fn (OfferStatusEnum $state): string => $state->label())
                    ->color(fn (OfferStatusEnum $state): string => match ($state) {
                        OfferStatusEnum::PUBLISHED => 'success',
                        OfferStatusEnum::DRAFT => 'gray',
                        OfferStatusEnum::ARCHIVED => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('price')
                    ->label(__('offer::offer.price'))
                    ->state(fn (Offer $record): ?string => $record->price === null ? null : $record->price.' '.$record->currency)
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('offer::offer.category'))
                    ->state(fn (Offer $record): ?string => $record->category?->path()),
                TextColumn::make('published_at')
                    ->label(__('offer::offer.published_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('offer::offer.status'))
                    ->options(OfferStatusEnum::options()),
                SelectFilter::make('category_id')
                    ->label(__('offer::offer.category'))
                    ->options(fn (): array => OfferCategory::options()),
                SelectFilter::make('user_id')
                    ->label(__('offer::offer.owner'))
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn (User $user): string => $user->getFilamentName())
                    ->searchable(),
                TrashedFilter::make(),
            ])
            ->recordActions([...OfferResource::getActions(), RestoreAction::make()])
            ->toolbarActions([...OfferResource::getBulkActions(), RestoreBulkAction::make()]);
    }
}
