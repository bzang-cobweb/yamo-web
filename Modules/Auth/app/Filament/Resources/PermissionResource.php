<?php

namespace Modules\Auth\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Auth\Filament\Resources\PermissionResource\InfoLists\PermissionInfolist;
use Modules\Auth\Filament\Resources\PermissionResource\Pages\ListPermissions;
use Modules\Auth\Filament\Resources\PermissionResource\Pages\ViewPermission;
use Modules\Auth\Filament\Resources\PermissionResource\Tables\PermissionTable;
use Modules\Auth\Models\Permission;

/**
 * Les permissions sont déclarées dans PermissionEnum : ressource en lecture seule.
 */
class PermissionResource extends AbstractAuthResource
{
    protected static ?string $model = Permission::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return PermissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'view' => ViewPermission::route('/{record}'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('auth::permission.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('auth::permission.plural');
    }
}
