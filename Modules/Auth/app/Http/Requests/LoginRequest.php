<?php

namespace Modules\Auth\Http\Requests;

use Modules\Auth\Forms\LoginForm;
use Modules\Form\Http\Requests\FormBackedRequest;

/**
 * Connexion, première étape : règles du formulaire `auth.login` (e-mail ou
 * téléphone, mot de passe).
 */
class LoginRequest extends FormBackedRequest
{
    /**
     * Route publique, limitée en fréquence (`throttle:auth-login`).
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): LoginForm
    {
        return new LoginForm;
    }
}
