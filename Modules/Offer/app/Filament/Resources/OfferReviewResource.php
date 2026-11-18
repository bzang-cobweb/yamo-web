<?php

namespace Modules\Offer\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Offer\Filament\Resources\OfferReviewResource\Pages\ListOfferReviews;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;

/**
 * Modération des avis (décision 2026-11-19-contrat-avis-offres) : la liste des
 * avis, filtrable par note et par offre ; un avis se supprime, il ne se crée ni ne
 * se modifie dans le back-office.
 */
class OfferReviewResource extends AbstractOfferResource
{
    protected static ?string $model = OfferReview::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            // Offre et auteur sans requête par ligne.
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['offer', 'user']))
            ->columns([
                TextColumn::make('offer.title')
                    ->label(__('offer::review.resource.offer'))
                    ->state(fn (OfferReview $record): string => (string) $record->offer?->getTranslation('title', app()->getLocale())),
                TextColumn::make('user.name')
                    ->label(__('offer::review.resource.author'))
                    ->state(fn (OfferReview $record): string => (string) $record->user?->getFilamentName())
                    ->searchable(['name', 'first_name', 'last_name']),
                TextColumn::make('rating')
                    ->label(__('offer::review.resource.rating'))
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state))
                    ->sortable(),
                TextColumn::make('comment')
                    ->label(__('offer::review.resource.comment'))
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('offer::review.resource.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->label(__('offer::review.resource.rating'))
                    ->options(array_combine(range(1, 5), array_map(fn (int $stars): string => str_repeat('★', $stars), range(1, 5)))),
                SelectFilter::make('offer_id')
                    ->label(__('offer::review.resource.offer'))
                    ->options(fn (): array => Offer::query()->whereHas('reviews')->orderByDesc('id')->get()
                        ->mapWithKeys(fn (Offer $offer): array => [$offer->getKey() => (string) $offer->getTranslation('title', app()->getLocale())])
                        ->all())
                    ->searchable(),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions(self::getBulkActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOfferReviews::route('/'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('offer::review.resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('offer::review.resource.plural');
    }
}
