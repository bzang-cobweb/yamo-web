<?php

namespace Modules\Auth\Filament\Resources\UserResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Auth\Filament\Resources\UserResource;

abstract class UserTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('auth::user.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('auth::user.email'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label(__('auth::role.title'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('auth::user.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label(__('auth::role.title'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->recordActions(UserResource::getActions())
            ->toolbarActions(UserResource::getBulkActions());
    }
}
