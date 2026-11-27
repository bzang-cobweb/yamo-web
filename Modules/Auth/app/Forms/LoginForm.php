<?php

namespace Modules\Auth\Forms;

use Bza\Form\Support\Form;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Forms\Concerns\HasIdentifierField;

/**
 * Connexion de l'app mobile, première étape : e-mail ou téléphone (l'indicatif du
 * pays courant d'office), sans mot de passe (décision 2026-11-27-connexion-sans-mot-de-passe).
 * Compte trouvé : un code part vers cet e-mail ou ce téléphone (formulaire `auth.code`) ;
 * sinon, l'app ouvre l'inscription avec cet identifiant. `device_name` part avec le code.
 */
class LoginForm extends Form
{
    use HasIdentifierField;

    public static function name(): string
    {
        return 'auth.login';
    }

    public function fields(): array
    {
        return $this->identifierFields();
    }

    public function submitLabel(): string
    {
        return __('auth::form.login.submit');
    }

    public function title(): string
    {
        return __('auth::form.login.title');
    }

    public function subtitle(): string
    {
        return __('auth::form.login.subtitle');
    }

    /**
     * Public : c'est le formulaire qu'on remplit avant d'être connecté.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }
}
