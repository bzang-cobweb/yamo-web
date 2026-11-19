<?php

namespace Modules\Location\Filament\Resources\CountryResource\Tables;

use App\Support\Currency;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Location\Filament\Resources\CountryResource;
use Modules\Location\Models\Country;

abstract class CountryTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label(__('location::country.code'))
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('label')
                    ->label(__('location::country.label'))
                    ->state(fn (Country $record): string => $record->label())
                    // Recherche dans toutes les langues du nom et dans le nom natif.
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where(fn (Builder $query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('native_name', 'like', "%{$search}%"))),
                TextColumn::make('dial_code')
                    ->label(__('location::country.dial_code'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('currency')
                    ->label(__('location::country.currency'))
                    // Le code saisi, et le symbole que lit une personne : « XAF (FCFA) »
                    // (décision 2026-11-19-contrat-formulaire-etapes-symbole-devise).
                    ->formatStateUsing(fn (?string $state): ?string => $state === null ? null : (($symbol = Currency::symbol($state)) === $state ? $state : $state.' ('.$symbol.')'))
                    ->sortable(),
                TextColumn::make('regions_count')
                    ->label(__('location::country.regions_count'))
                    ->counts('regions')
                    ->sortable(),
                ToggleColumn::make('active')
                    ->label(__('location::country.active'))
                    ->disabled(fn (Country $record): bool => ! (auth()->user()?->can('update', $record) ?? false))
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label(__('location::country.active')),
            ])
            ->recordActions(CountryResource::getActions())
            ->toolbarActions(CountryResource::getBulkActions());
    }
}
