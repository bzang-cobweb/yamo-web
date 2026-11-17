<?php

namespace Modules\Auth\Filament\Resources\UserResource\Tables;

use App\Support\Locales;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Auth\Filament\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AvatarService;

abstract class UserTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->label(__('auth::user.avatar'))
                    ->collection(AvatarService::COLLECTION)
                    ->conversion(AvatarService::THUMB)
                    ->circular()
                    ->imageSize(36),
                // Pastille « en ligne » : vu par l'API dans les 5 dernières minutes.
                IconColumn::make('is_online')
                    ->label(__('auth::user.online'))
                    ->boolean()
                    ->state(fn (User $record): bool => $record->is_online),
                TextColumn::make('name')
                    ->label(__('auth::user.name'))
                    ->sortable()
                    ->searchable(),
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
                // Compte supprimé par son titulaire, restaurable jusqu'à sa purge.
                TextColumn::make('deleted_at')
                    ->label(__('auth::user.deleted_at'))
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
                TrashedFilter::make()->label(__('auth::user.deleted_filter')),
            ])
            ->recordActions(UserResource::getActions())
            ->toolbarActions(UserResource::getBulkActions());
    }
}
