<?php

namespace Modules\Auth\Filament\Resources;

use App\Filament\AbstractResource;
use App\Filament\NavigationGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Auth\Filament\Resources\ProfileMenuLinkResource\Pages\ListProfileMenuLinks;
use Modules\Auth\Models\ProfileMenuLink;

/**
 * Menu du profil de l'app, sous Paramètres : l'administrateur ordonne les liens
 * par glisser-déposer et masque ceux qu'il ne veut pas. Pas de création ni de
 * suppression : chaque lien est un écran de l'app (décision
 * 2026-11-17-tri-regions-menu-profil).
 */
class ProfileMenuLinkResource extends AbstractResource
{
    protected static ?string $model = ProfileMenuLink::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 110;

    protected static ?string $slug = 'profile-menu';

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->paginated(false)
            ->columns([
                TextColumn::make('key')
                    ->label(__('auth::profile_menu.screen'))
                    ->formatStateUsing(fn (ProfileMenuLink $record): string => $record->key->label()),
                ToggleColumn::make('active')
                    ->label(__('auth::profile_menu.active'))
                    ->disabled(fn (ProfileMenuLink $record): bool => ! (auth()->user()?->can('update', $record) ?? false)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProfileMenuLinks::route('/'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return NavigationGroup::Settings->label();
    }

    public static function getNavigationLabel(): string
    {
        return __('auth::profile_menu.title');
    }

    public static function getModelLabel(): string
    {
        return __('auth::profile_menu.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('auth::profile_menu.title');
    }
}
