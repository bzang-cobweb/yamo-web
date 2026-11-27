<?php

namespace Modules\Auth\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Filament\Resources\UserResource\Pages\CreateUser;
use Modules\Auth\Filament\Resources\UserResource\Pages\EditUser;
use Modules\Auth\Filament\Resources\UserResource\Pages\ListUsers;
use Modules\Auth\Filament\Resources\UserResource\RelationManagers\AddressesRelationManager;
use Modules\Auth\Filament\Resources\UserResource\RelationManagers\PhonesRelationManager;
use Modules\Auth\Filament\Resources\UserResource\Schemas\UserForm;
use Modules\Auth\Filament\Resources\UserResource\Tables\UserTable;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;

class UserResource extends AbstractAuthResource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'last_name';

    /**
     * Actions communes, plus la restauration et la purge d'un compte supprimé
     * par son titulaire (décision 2026-11-20-suppression-de-son-compte).
     *
     * @return array<int, Action>
     */
    public static function getActions(): array
    {
        return [
            ...parent::getActions(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserTable::configure($table);
    }

    /**
     * Ses adresses, sur le pivot (décision 2026-11-18-adresses-liste-generique).
     */
    public static function getRelations(): array
    {
        return [
            AddressesRelationManager::class,
            // Numéros supplémentaires (décision 2026-11-27-plusieurs-numeros).
            PhonesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record instanceof User ? $record->full_name : null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        // Les identifiants ne sont cherchés que sur les canaux actifs de l'application.
        return [
            'name', 'first_name', 'last_name',
            ...(AuthChannels::email() ? ['email'] : []),
            ...(AuthChannels::phone() ? ['phone'] : []),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('auth::user.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('auth::user.plural');
    }
}
