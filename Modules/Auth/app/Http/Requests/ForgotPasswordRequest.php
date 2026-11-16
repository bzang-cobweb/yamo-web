<?php

namespace Modules\Auth\Http\Requests;

use Modules\Auth\Forms\ForgotPasswordForm;
use Modules\Form\Http\Requests\FormBackedRequest;

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
