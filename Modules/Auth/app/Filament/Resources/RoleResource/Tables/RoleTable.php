<?php

namespace Modules\Auth\Filament\Resources\RoleResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Auth\Filament\Resources\RoleResource;

abstract class RoleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('auth::role.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('permissions_count')
                    ->label(__('auth::permission.title'))
                    ->counts('permissions')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label(__('auth::user.title'))
                    ->counts('users')
                    ->sortable(),
            ])
            ->recordActions(RoleResource::getActions())
            ->toolbarActions(RoleResource::getBulkActions());
    }
}
