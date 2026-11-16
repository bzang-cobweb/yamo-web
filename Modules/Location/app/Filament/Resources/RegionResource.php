<?php

namespace Modules\Location\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\Location\Filament\Resources\RegionResource\Pages\CreateRegion;
use Modules\Location\Filament\Resources\RegionResource\Pages\EditRegion;
use Modules\Location\Filament\Resources\RegionResource\Pages\ListRegions;
use Modules\Location\Filament\Resources\RegionResource\Schemas\RegionForm;
use Modules\Location\Filament\Resources\RegionResource\Tables\RegionTable;
use Modules\Location\Models\Region;

class RegionResource extends AbstractLocationResource
{
    use Translatable;

    protected static ?string $model = Region::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return RegionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegionTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegions::route('/'),
            'create' => CreateRegion::route('/create'),
            'edit' => EditRegion::route('/{record}/edit'),
        ];
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
