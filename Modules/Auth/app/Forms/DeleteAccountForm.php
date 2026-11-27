<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;

/**
 * Supprimer son compte, depuis le profil de l'app mobile
 * (`DELETE /api/v1/auth/me`) : un avertissement, puis la raison (obligatoire) ; un code part ensuite à l'e-mail
 * vérifié, sinon au numéro vérifié, et sa validation supprime le compte (décision 2026-11-27-confidentialite).
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
            // La raison, obligatoire, gardée pour l'équipe (décision 2026-11-27-confidentialite).
            Field::textarea('reason')
                ->label(__('auth::form.delete_account.reason'))
                ->help(__('auth::form.delete_account.reason_help'))
                ->required(),
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
