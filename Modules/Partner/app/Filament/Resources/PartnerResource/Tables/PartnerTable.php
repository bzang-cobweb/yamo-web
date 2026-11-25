<?php

namespace Modules\Partner\Filament\Resources\PartnerResource\Tables;

use App\Filament\AbstractResource;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Filament\Resources\PartnerResource;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;

/**
 * Les partenaires, dans leur ordre d'affichage, triables par glisser-déposer ; leur campagne et leurs compteurs
 * (décisions 2026-11-23-module-partenaires et 2026-11-23-partenaires-triables).
 */
abstract class PartnerTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            // Glisser-déposer pour l'ordre d'affichage dans l'app (décision 2026-11-23-partenaires-triables).
            ->reorderable('position')
            ->modifyQueryUsing(fn ($query) => $query->with(['country', 'media']))
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->label(__('partner::partner.fields.logo'))
                    ->collection(Partner::LOGO),
                TextColumn::make('name')
                    ->label(__('partner::partner.fields.name'))
                    ->state(fn (Partner $record): string => (string) $record->getTranslation('name', app()->getLocale()))
                    // Le nom traduit : la recherche porte sur toutes ses langues, comme dans l'app (décision
                    // 2026-11-23-partenaires-recherche).
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(fn (Builder $group) => PartnerService::search($group, $search))),
                TextColumn::make('country.code')
                    ->label(__('partner::partner.fields.country'))
                    ->state(fn (Partner $record): string => $record->country->label()),
                TextColumn::make('status')
                    ->label(__('partner::partner.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (PartnerStatusEnum $state): string => $state->label())
                    ->color(fn (PartnerStatusEnum $state): string => $state->color()),
                TextColumn::make('starts_at')
                    ->label(__('partner::partner.fields.starts_at'))
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('partner::partner.fields.expires_at'))
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                // Décision 2026-11-25-vedettes-d-abord.
                TextColumn::make('featured_until')
                    ->label(__('partner::partner.fields.featured_until'))
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('views_count')
                    ->label(__('partner::partner.fields.views_count'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clicks_count')
                    ->label(__('partner::partner.fields.clicks_count'))
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('partner::partner.fields.status'))
                    ->options(PartnerStatusEnum::options()),
                // Proposés depuis l'app, à valider (décision 2026-11-24-partenaires-par-les-membres).
                TernaryFilter::make('proposed')
                    ->label(__('partner::partner.fields.proposed'))
                    ->queries(
                        true: fn ($query) => $query->has('users'),
                        false: fn ($query) => $query->doesntHave('users'),
                    ),
            ])
            // « Valider » avant Modifier et Supprimer (décision 2026-11-24-partenaires-validation-back-office).
            ->recordActions([PartnerResource::validateAction(), ...AbstractResource::getActions()])
            ->toolbarActions(AbstractResource::getBulkActions());
    }
}
