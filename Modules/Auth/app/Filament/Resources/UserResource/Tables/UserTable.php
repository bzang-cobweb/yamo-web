<?php

namespace Modules\Auth\Filament\Resources\UserResource\Tables;

use App\Support\Locales;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\Auth\Filament\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AvatarService;
use Modules\Auth\Support\AuthChannels;
use Modules\Auth\Support\PhoneNumbers;

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
                    ->conversion('list')
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
                    ->searchable()
                    ->visible(AuthChannels::email()),
                TextColumn::make('phone')
                    ->label(__('auth::user.phone'))
                    ->formatStateUsing(fn (string $state): string => PhoneNumbers::international($state))
                    ->searchable()
                    ->visible(AuthChannels::phone())
                    ->toggleable(isToggledHiddenByDefault: AuthChannels::email()),
                TextColumn::make('locale')
                    ->label(__('auth::user.locale'))
                    ->formatStateUsing(fn (string $state): string => Locales::label($state))
                    ->badge()
                    ->sortable(),
                // Identifié par l'équipe (décision 2026-11-25-identification).
                IconColumn::make('identified_at')
                    ->label(__('auth::identity.admin.identified'))
                    ->boolean()
                    ->state(fn (User $record): bool => $record->isIdentified())
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
                TernaryFilter::make('identified_at')
                    ->label(__('auth::identity.admin.identified'))
                    ->nullable(),
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
