<?php

namespace Modules\Auth\Http\Requests;

use Modules\Auth\Forms\RegisterForm;
use Modules\Form\Http\Requests\FormBackedRequest;

/**
 * Créer un compte : toutes les règles du formulaire `auth.register`, unicité du
 * pseudonyme, de l'e-mail et du téléphone comprise (déclarée sur ses champs, donc
 * vérifiée aussi à chaque étape).
 */
class RegisterRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): RegisterForm
    {
        return new RegisterForm;
    }
}
