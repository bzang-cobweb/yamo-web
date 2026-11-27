<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\ForgotPasswordForm;

/**
 * Mot de passe oublié, première étape : règles du formulaire `auth.password.forgot`.
 */
class ForgotPasswordRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): ForgotPasswordForm
    {
        return new ForgotPasswordForm;
    }
}
