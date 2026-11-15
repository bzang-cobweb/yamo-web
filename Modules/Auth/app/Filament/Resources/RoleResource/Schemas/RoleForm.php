<?php

namespace Modules\Auth\Filament\Resources\RoleResource\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Auth\Models\Permission;

abstract class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                static::getGeneralSection(),
                static::getPermissionsSection(),
            ]);
    }

    public static function getGeneralSection(): Section
    {
        return Section::make(__('auth::role.sections.general'))
            ->schema([
                TextInput::make('name')
                    ->label(__('auth::role.name'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required(),
            ]);
    }

    public static function getPermissionsSection(): Section
    {
        return Section::make(__('auth::permission.title'))
            ->schema([
                CheckboxList::make('permissions')
                    ->hiddenLabel()
                    ->relationship('permissions', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Permission $record): string => $record->label())
                    ->columns(2)
                    ->bulkToggleable(),
            ]);
    }
}
