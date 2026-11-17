<?php

namespace Modules\Auth\Forms;

use Illuminate\Validation\Rules\Password;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;

/**
 * Changer son mot de passe, depuis le profil de l'app mobile
 * (`PUT /api/v1/auth/me/password`) : l'ancien, le nouveau et sa confirmation.
 */
class PasswordForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.password';
    }

    public function fields(): array
    {
        return [
            // `current_password` vise le garde de la requête : sanctum sur l'API.
            Field::password('current_password')
                ->label(__('auth::user.current_password'))
                ->required()
                ->rules(['current_password']),
            Field::password('password')
                ->label(__('auth::user.new_password'))
                ->help(__('auth::form.password.help'))
                ->required()
                ->rules([Password::defaults(), 'confirmed', 'different:current_password']),
            Field::password('password_confirmation')
                ->label(__('auth::user.password_confirmation'))
                ->required(),
        ];
    }

    public function title(): string
    {
        return __('auth::form.password.title');
    }

    /**
     * L'app envoie le formulaire à `api.v1.auth.me.password.update`
     * (décision 2026-11-17-formulaires-profil-generiques).
     */
    public function submission(): Submission
    {
        return Submission::toRoute('api.v1.auth.me.password.update', SuccessAction::PROFILE, __('auth::mobile.profile.passwordSaved'));
    }

    public function submitLabel(): string
    {
        return __('auth::form.password.submit');
    }
}
