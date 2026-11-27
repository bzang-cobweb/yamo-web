<?php

namespace Modules\Auth\Filament\Resources\UserResource\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserPhone;
use Modules\Auth\Support\PhoneNumbers;

/**
 * Les numéros supplémentaires d'un utilisateur (décision 2026-11-27-plusieurs-numeros), en entier comme le reste du
 * back-office : le numéro, son pays, sa vérification. Un numéro s'ajoute seulement depuis l'app, par un code SMS ; ici,
 * on peut le retirer.
 */
class PhonesRelationManager extends RelationManager
{
    protected static string $relationship = 'phones';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone')
            ->modifyQueryUsing(fn ($query) => $query->with('phoneCountry'))
            ->columns([
                TextColumn::make('phone')
                    ->label(__('auth::user.phone'))
                    ->state(fn (UserPhone $record): string => PhoneNumbers::international($record->phone))
                    ->searchable(),
                TextColumn::make('phoneCountry.code')
                    ->label(__('auth::user.phone_country'))
                    ->state(fn (UserPhone $record): ?string => $record->phoneCountry?->label()),
                TextColumn::make('verified_at')
                    ->label(__('auth::phones.verified_at'))
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('auth::phones.added_at'))
                    ->dateTime(),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label(__('auth::phones.list.remove')),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        /** @var User $ownerRecord */
        return __('auth::phones.title').' ('.$ownerRecord->phones()->count().')';
    }
}
