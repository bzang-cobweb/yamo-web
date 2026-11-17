<?php

namespace Modules\Location\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Location\Filament\Resources\CountryResource\Pages\CreateCountry;
use Modules\Location\Filament\Resources\CountryResource\Pages\EditCountry;
use Modules\Location\Filament\Resources\CountryResource\Pages\ListCountries;
use Modules\Location\Filament\Resources\CountryResource\RelationManagers\RegionsRelationManager;
use Modules\Location\Filament\Resources\CountryResource\Schemas\CountryForm;
use Modules\Location\Filament\Resources\CountryResource\Tables\CountryTable;
use Modules\Location\Models\Country;

class CountryResource extends AbstractLocationResource
{
    protected static ?string $model = Country::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return CountryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountryTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RegionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record instanceof Country ? $record->label() : null;
    }

    public static function getModelLabel(): string
    {
        return __('location::country.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('location::country.plural');
    }
}
