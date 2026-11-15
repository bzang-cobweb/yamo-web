<?php

namespace Modules\Auth\Filament\Resources\UserResource\Tables;

use App\Support\Locales;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Auth\Filament\Resources\UserResource;

abstract class UserTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->columns([
                TextColumn::make('first_name')
                    ->label(__('auth::user.first_name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label(__('auth::user.last_name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('auth::user.email'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('locale')
                    ->label(__('auth::user.locale'))
                    ->formatStateUsing(fn (string $state): string => Locales::label($state))
                    ->badge()
                    ->sortable(),
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
                SelectFilter::make('locale')
                    ->label(__('auth::user.locale'))
                    ->options(Locales::options()),
            ])
            ->recordActions(UserResource::getActions())
            ->toolbarActions(UserResource::getBulkActions());
    }
}
