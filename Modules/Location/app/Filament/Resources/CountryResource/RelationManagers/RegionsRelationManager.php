<?php

namespace Modules\Location\Filament\Resources\CountryResource\RelationManagers;

use App\Filament\AbstractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Location\Filament\Resources\CountryResource\Schemas\RegionForm;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;

/**
 * Régions d'un pays, gérées depuis sa fiche (décision
 * 2026-11-18-contrat-etapes-mot-de-passe-oublie). Chaque action passe par
 * `RegionPolicy`.
 */
class RegionsRelationManager extends RelationManager
{
    protected static string $relationship = 'regions';

    public function form(Schema $schema): Schema
    {
        return RegionForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('id')
            ->columns([
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
                TernaryFilter::make('active')
                    ->label(__('location::region.active')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions(AbstractResource::getActions())
            ->toolbarActions(AbstractResource::getBulkActions());
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        /** @var Country $ownerRecord */
        return __('location::region.title').' ('.$ownerRecord->regions()->count().')';
    }

    public static function getModelLabel(): string
    {
        return __('location::region.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('location::region.plural');
    }
}
