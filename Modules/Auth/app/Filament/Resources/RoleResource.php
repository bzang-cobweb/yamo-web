<?php

namespace Modules\Auth\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Auth\Filament\Resources\RoleResource\Pages\CreateRole;
use Modules\Auth\Filament\Resources\RoleResource\Pages\EditRole;
use Modules\Auth\Filament\Resources\RoleResource\Pages\ListRoles;
use Modules\Auth\Filament\Resources\RoleResource\Schemas\RoleForm;
use Modules\Auth\Filament\Resources\RoleResource\Tables\RoleTable;
use Modules\Auth\Models\Role;

class RoleResource extends AbstractAuthResource
{
    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoleTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('auth::role.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('auth::role.plural');
    }
}
