<?php

namespace Modules\Offer\Filament\Resources\OfferResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;

/**
 * Les avis d'une offre, sur sa fiche du back-office (décision
 * 2026-11-19-acces-avis-backoffice) : filtrables par note, supprimables ; un avis ne
 * se crée ni ne se modifie ici (`OfferReviewPolicy`).
 */
class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('offer::review.resource.author'))
                    ->state(fn (OfferReview $record): string => (string) $record->user?->getFilamentName())
                    ->searchable(['name', 'first_name', 'last_name']),
                TextColumn::make('rating')
                    ->label(__('offer::review.resource.rating'))
                    ->formatStateUsing(fn (float|string $state): string => Number::format((float) $state, precision: 1, locale: app()->getLocale()).' ★')
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
                    // Par étoile : « ★★★ » garde les notes de 3,0 à 3,9 (décision 2026-11-27-note-au-dixieme).
                    ->options(array_combine(range(1, 5), array_map(fn (int $stars): string => str_repeat('★', $stars), range(1, 5))))
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null) ? $query : $query->where('rating', '>=', (int) $data['value'])->where('rating', '<', (int) $data['value'] + 1)),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->authorizeIndividualRecords('delete')])]);
    }

    /**
     * « Avis (12) ».
     */
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        /** @var Offer $ownerRecord */
        return __('offer::offer.reviews').' ('.$ownerRecord->reviews()->count().')';
    }
}
