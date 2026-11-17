<?php

namespace Modules\Auth\Forms;

use Modules\Form\Support\Field;
use Modules\Form\Support\Form;

/**
 * Supprimer son compte, depuis le profil de l'app mobile
 * (`DELETE /api/v1/auth/me`) : un avertissement, puis le mot de passe actuel.
 */
class DeleteAccountForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.delete-account';
    }

    public function fields(): array
    {
        return [
            // `current_password` vise le garde de la requête : sanctum sur l'API.
            Field::password('current_password')
                ->label(__('auth::user.current_password'))
                ->required()
                ->rules(['current_password']),
        ];
    }

    public function title(): string
    {
        return __('auth::form.delete_account.title');
    }

    public function subtitle(): string
    {
        return __('auth::form.delete_account.warning', ['days' => config('auth.account_deletion.grace_days')]);
    }

    public function submitLabel(): string
    {
        return __('auth::form.delete_account.submit');
    }
}
