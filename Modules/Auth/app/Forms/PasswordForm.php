<?php

namespace Modules\Auth\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Models\User;

/**
 * Changer son mot de passe, depuis le profil de l'app mobile
 * (`PUT /api/v1/auth/me/password`) : l'ancien, le nouveau et sa confirmation. Un
 * compte qui n'en a pas (inscrit sans, décision 2026-11-27-connexion-sans-mot-de-passe)
 * le crée : ni l'ancien, ni la comparaison avec lui.
 */
class PasswordForm extends Form
{
    public static function name(): string
    {
        return 'auth.profile.password';
    }

    public function fields(): array
    {
        $hasPassword = $this->hasPassword();

        return [
            // `current_password` vise le garde de la requête : sanctum sur l'API.
            ...($hasPassword ? [Field::password('current_password')
                ->label(__('auth::user.current_password'))
                ->required()
                ->rules(['current_password'])] : []),
            Field::password('password')
                ->label(__('auth::user.new_password'))
                ->help(__('auth::form.password.help'))
                ->required()
                ->rules([Password::defaults(), 'confirmed', ...($hasPassword ? ['different:current_password'] : [])]),
            Field::password('password_confirmation')
                ->label(__('auth::user.password_confirmation'))
                ->required(),
        ];
    }

    public function title(): string
    {
        return $this->hasPassword() ? __('auth::form.password.title') : __('auth::form.password.create_title');
    }

    /**
     * Le compte a déjà un mot de passe.
     */
    private function hasPassword(): bool
    {
        return ! $this->user instanceof User || $this->user->password !== null;
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
