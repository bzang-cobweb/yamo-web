<?php

namespace Modules\Auth\Forms;

use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;

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

    /**
     * L'app envoie le formulaire à `api.v1.auth.me.destroy`
     * (décision 2026-11-17-formulaires-profil-generiques).
     */
    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.destroy', SuccessAction::SIGN_OUT, __('auth::mobile.auth.accountDeleted'), destructive: true);
    }

    public function submitLabel(): string
    {
        return __('auth::form.delete_account.submit');
    }
}
