<?php

namespace Modules\Partner\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Partner\Models\Partner;

/**
 * Les comptes rattachés à un partenaire (décision 2026-11-24-partenaires-par-les-membres) : ils le modifient depuis
 * l'app ; un administrateur (`is_admin`) peut aussi le retirer. On les rattache et on les détache ici, et on coche
 * qui est administrateur ; il faut pouvoir modifier le partenaire.
 */
class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (User $record): string => $record->getFilamentName().' ('.$record->email.')')
            ->columns([
                TextColumn::make('name')
                    ->label(__('partner::partner.fields.user'))
                    ->state(fn (User $record): string => $record->getFilamentName())
                    ->searchable(['name', 'first_name', 'last_name', 'email']),
                TextColumn::make('email')
                    ->label(__('partner::partner.fields.contact_email')),
                IconColumn::make('pivot.is_admin')
                    ->label(__('partner::partner.fields.is_admin'))
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectSearchColumns(['name', 'first_name', 'last_name', 'email'])
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Toggle::make('is_admin')->label(__('partner::partner.fields.is_admin')),
                    ])
                    ->visible(fn (): bool => $this->canManage()),
            ])
            ->recordActions([
                // Administrateur ou pas : un geste.
                Action::make('toggleAdmin')
                    ->label(fn (User $record): string => $record->pivot->is_admin ? __('partner::partner.fields.remove_admin') : __('partner::partner.fields.make_admin'))
                    ->action(fn (User $record) => $this->getOwnerRecord()->users()->updateExistingPivot($record->getKey(), ['is_admin' => ! $record->pivot->is_admin]))
                    ->visible(fn (): bool => $this->canManage()),
                DetachAction::make()->visible(fn (): bool => $this->canManage()),
            ]);
    }

    private function canManage(): bool
    {
        return (bool) auth()->user()?->can('update', $this->getOwnerRecord());
    }

    /**
     * « Comptes (2) ».
     */
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        /** @var Partner $ownerRecord */
        return __('partner::partner.admins').' ('.$ownerRecord->users()->count().')';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('view', $ownerRecord);
    }

    protected function canAttach(): bool
    {
        return $this->canManage();
    }

    protected function canDetach(Model $record): bool
    {
        return $this->canManage();
    }
}
