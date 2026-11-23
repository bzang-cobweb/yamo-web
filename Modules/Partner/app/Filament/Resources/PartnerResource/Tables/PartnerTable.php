<?php

namespace Modules\Partner\Filament\Resources\PartnerResource\Tables;

use App\Filament\AbstractResource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Partner\Models\Partner;

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
                    // Le nom traduit : la recherche porte sur toutes ses langues.
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('name', 'like', '%'.$search.'%')),
                TextColumn::make('country.code')
                    ->label(__('partner::partner.fields.country'))
                    ->state(fn (Partner $record): string => $record->country->label()),
                IconColumn::make('published')
                    ->label(__('partner::partner.fields.published'))
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->label(__('partner::partner.fields.starts_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('partner::partner.fields.expires_at'))
                    ->dateTime()
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
                TernaryFilter::make('published')
                    ->label(__('partner::partner.fields.published')),
            ])
            ->recordActions(AbstractResource::getActions())
            ->toolbarActions(AbstractResource::getBulkActions());
    }
}
