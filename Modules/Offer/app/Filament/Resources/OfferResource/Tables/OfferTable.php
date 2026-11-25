<?php

namespace Modules\Offer\Filament\Resources\OfferResource\Tables;

use App\Support\ContainsSearch;
use App\Support\Locales;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
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
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'country', 'category.parent.parent']))
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
                TextColumn::make('country.code')
                    ->label(__('offer::offer.country'))
                    ->badge()
                    ->sortable(),
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
                    ->state(fn (Offer $record): ?string => $record->price === null ? null : Number::currency((float) $record->price, (string) $record->currency, app()->getLocale()))
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('offer::offer.category'))
                    ->state(fn (Offer $record): ?string => $record->category?->path()),
                TextColumn::make('published_at')
                    ->label(__('offer::offer.published_at'))
                    ->dateTime()
                    ->sortable(),
                // Décision 2026-11-19-contrat-vedette-favoris.
                TextColumn::make('featured_until')
                    ->label(__('offer::offer.featured_until'))
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                // Décision 2026-11-25-remontee-carrousel.
                TextColumn::make('boosted_until')
                    ->label(__('offer::offer.boosted_until'))
                    ->date()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                // Décision 2026-11-25-compteurs-offres.
                TextColumn::make('views_count')
                    ->label(__('offer::offer.views_count'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('clicks_count')
                    ->label(__('offer::offer.clicks_count'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('offer::offer.status'))
                    ->options(OfferStatusEnum::options()),
                SelectFilter::make('country_id')
                    ->label(__('offer::offer.country'))
                    ->options(fn (): array => Country::options()),
                SelectFilter::make('category_id')
                    ->label(__('offer::offer.category'))
                    ->options(fn (): array => OfferCategory::options()),
                SelectFilter::make('user_id')
                    ->label(__('offer::offer.owner'))
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn (User $user): string => $user->getFilamentName())
                    ->searchable(),
                TernaryFilter::make('featured')
                    ->label(__('offer::offer.featured'))
                    ->queries(
                        true: fn ($query) => $query->featured(),
                        false: fn ($query) => $query->notFeatured(),
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([...OfferResource::getActions(), RestoreAction::make()])
            ->toolbarActions([...OfferResource::getBulkActions(), RestoreBulkAction::make()]);
    }
}
