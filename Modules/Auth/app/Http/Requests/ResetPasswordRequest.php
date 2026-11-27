<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\ResetPasswordForm;

/**
 * Nouveau mot de passe : règles du formulaire `auth.password.reset`, plus le défi
 * dont le code a été validé.
 */
class ResetPasswordRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): ResetPasswordForm
    {
        return new ResetPasswordForm;
    }

    public function rules(): array
    {
        return [...parent::rules(), 'challenge' => ['required', 'uuid']];
    }
}
