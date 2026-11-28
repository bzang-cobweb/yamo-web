<?php

namespace Modules\Auth\Forms;

use Bza\Form\Support\Form;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Auth\Forms\Concerns\HasIdentifierField;

/**
 * Se connecter ou s'inscrire, première étape : e-mail ou téléphone (l'indicatif du pays courant d'office, tous les pays
 * proposés). Un code part toujours vers cet identifiant (formulaire `auth.code`), compte connu ou non ; viennent
 * ensuite le mot de passe, puis l'inscription pour un nouveau compte (décision 2026-11-28-connexion-panneau).
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
