<?php

namespace Modules\Auth\Forms;

use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * L'étape du mot de passe d'un compte qui en a un (`mode: sign-in`), après le code (décision
 * 2026-11-28-connexion-panneau). L'app l'envoie à `POST /auth/session` avec le défi ; « Mot de passe oublié ? » y
 * mène au formulaire `auth.session.new-password`, envoyé avec `reset: true`.
 */
class SessionPasswordForm extends Form
{
    public static function name(): string
    {
        return 'auth.session.password';
    }

    public function fields(): array
    {
        return [
            Field::password('password')->label(__('auth::user.password'))->required()->rules(['string', 'max:255']),
        ];
    }

    public function title(): string
    {
        return __('auth::form.session.password.title');
    }

    public function submitLabel(): string
    {
        return __('auth::form.session.password.submit');
    }

    /**
     * Public : c'est le formulaire qu'on remplit avant d'être connecté.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }
}
