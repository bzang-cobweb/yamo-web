<?php

namespace Modules\Auth\Filament\Resources\UserResource\Pages;

use App\Filament\Pages\Concerns\HasRecordTitleBreadcrumbs;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Auth\Filament\Resources\UserResource;
use Modules\Auth\Models\User;

class EditUser extends EditRecord
{
    use HasRecordTitleBreadcrumbs;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->validateIdentityAction(),
            $this->revokeIdentityAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * « Valider l'identité » (décision 2026-11-25-identification) : après avoir regardé ses pièces d'identité, l'équipe
     * remplit la date d'identification, maintenant par défaut.
     */
    private function validateIdentityAction(): Action
    {
        return Action::make('validateIdentity')
            ->label(__('auth::identity.admin.validate'))
            ->icon('heroicon-o-identification')
            ->color('success')
            ->visible(fn (User $record): bool => ! $record->isIdentified())
            ->authorize(fn (User $record): bool => (bool) auth()->user()?->can('update', $record))
            ->schema([
                DateTimePicker::make('identified_at')
                    ->label(__('auth::identity.admin.identified_at'))
                    ->default(now())
                    ->maxDate(now()->endOfDay())
                    ->required(),
            ])
            ->action(function (User $record, array $data): void {
                $record->forceFill(['identified_at' => $data['identified_at']])->save();
                $this->refreshFormData(['identified_at']);
                Notification::make()->success()->title(__('auth::identity.admin.validated'))->send();
            });
    }

    /** Retirer l'identification : la date est vidée, le compte n'est plus affiché comme identifié. */
    private function revokeIdentityAction(): Action
    {
        return Action::make('revokeIdentity')
            ->label(__('auth::identity.admin.revoke'))
            ->icon('heroicon-o-x-circle')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (User $record): bool => $record->isIdentified())
            ->authorize(fn (User $record): bool => (bool) auth()->user()?->can('update', $record))
            ->action(function (User $record): void {
                $record->forceFill(['identified_at' => null])->save();
                $this->refreshFormData(['identified_at']);
                Notification::make()->success()->title(__('auth::identity.admin.revoked'))->send();
            });
    }
}
