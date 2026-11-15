<?php

namespace Modules\Auth\Filament\Resources\UserResource\Schemas;

use App\Support\Locales;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Modules\Auth\Enums\RoleEnum;

abstract class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                static::getGeneralSection(),
                static::getAccessSection(),
            ]);
    }

    public static function getGeneralSection(): Section
    {
        return Section::make(__('auth::user.sections.general'))
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->label(__('auth::user.name'))
                    ->helperText(__('auth::user.name_help'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('first_name')
                    ->label(__('auth::user.first_name'))
                    ->maxLength(255)
                    ->required(),
                TextInput::make('last_name')
                    ->label(__('auth::user.last_name'))
                    ->maxLength(255)
                    ->required(),
                TextInput::make('email')
                    ->label(__('auth::user.email'))
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required(),
                Select::make('locale')
                    ->label(__('auth::user.locale'))
                    ->options(Locales::options())
                    ->default(config('app.locale'))
                    ->selectablePlaceholder(false)
                    ->required(),
                TextInput::make('password')
                    ->label(__('auth::user.password'))
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->required(fn (Page $livewire): bool => $livewire instanceof CreateRecord)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (Page $livewire): ?string => $livewire instanceof CreateRecord ? null : __('auth::user.password_help')),
            ]);
    }

    public static function getAccessSection(): Section
    {
        return Section::make(__('auth::user.sections.access'))
            ->schema([
                Select::make('roles')
                    ->label(__('auth::role.title'))
                    ->relationship(
                        'roles',
                        'name',
                        // Seul un super administrateur peut attribuer le rôle super administrateur.
                        modifyQueryUsing: fn (Builder $query): Builder => auth()->user()?->isSuperAdmin()
                            ? $query
                            : $query->where('name', '!=', RoleEnum::SUPER_ADMIN->value),
                    )
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }
}
