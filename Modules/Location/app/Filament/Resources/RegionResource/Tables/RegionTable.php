<?php

namespace Modules\Location\Filament\Resources\RegionResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Location\Filament\Resources\RegionResource;
use Modules\Location\Filament\Resources\RegionResource\Schemas\RegionForm;
use Modules\Location\Models\Region;

abstract class RegionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('country'))
            ->defaultSort('country_id')
            ->columns([
                TextColumn::make('country.code')
                    ->label(__('location::region.country'))
                    ->formatStateUsing(fn (Region $record): string => $record->country->label())
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('location::region.name'))
                    ->state(fn (Region $record): string => (string) $record->getTranslation('name', app()->getLocale()))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('name', 'like', "%{$search}%")),
                ToggleColumn::make('active')
                    ->label(__('location::region.active'))
                    ->disabled(fn (Region $record): bool => ! (auth()->user()?->can('update', $record) ?? false))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label(__('location::region.country'))
                    ->options(fn (): array => RegionForm::countryOptions())
                    ->searchable(),
                TernaryFilter::make('active')
                    ->label(__('location::region.active')),
            ])
            ->recordActions(RegionResource::getActions())
            ->toolbarActions(RegionResource::getBulkActions());
    }
}
