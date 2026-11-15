<?php

namespace Modules\Auth\Filament\Resources\PermissionResource\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Auth\Models\Permission;

abstract class PermissionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('auth::permission.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('label')
                    ->label(__('auth::permission.label'))
                    ->state(fn (Permission $record): string => $record->label()),
                TextColumn::make('roles_count')
                    ->label(__('auth::role.title'))
                    ->counts('roles')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
