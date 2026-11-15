<?php

namespace Modules\Auth\Filament\Resources\PermissionResource\InfoLists;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Auth\Models\Permission;

abstract class PermissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('auth::permission.sections.general'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label(__('auth::permission.name')),
                        TextEntry::make('label')
                            ->label(__('auth::permission.label'))
                            ->state(fn (Permission $record): string => $record->label()),
                        TextEntry::make('roles.name')
                            ->label(__('auth::role.title'))
                            ->badge()
                            ->placeholder(__('auth::permission.no_roles')),
                    ]),
            ]);
    }
}
