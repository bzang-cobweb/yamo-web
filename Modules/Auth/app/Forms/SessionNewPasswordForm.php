<?php

namespace Modules\Auth\Forms;

use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\Rules\Password;

/**
 * Choisir un mot de passe à l'étape du mot de passe, après le code (décision 2026-11-28-connexion-panneau) : nouveau
 * compte (`mode: register`), compte sans mot de passe (`create`), ou mot de passe oublié (`sign-in` avec
 * `reset: true`). L'app l'envoie à `POST /auth/session` avec le défi.
 */
class SessionNewPasswordForm extends Form
{
    public static function name(): string
    {
        return 'auth.session.new-password';
    }

    public function fields(): array
    {
        return [
            Field::password('password')
                ->label(__('auth::user.password'))
                ->help(__('auth::form.password.help'))
                ->required()
                ->rules([Password::defaults(), 'confirmed']),
            Field::password('password_confirmation')->label(__('auth::user.password_confirmation_plain'))->required(),
        ];
    }

    public function title(): string
    {
        return __('auth::form.session.new_password.title');
    }

    public function submitLabel(): string
    {
        return __('auth::form.session.new_password.submit');
    }

    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }
}
