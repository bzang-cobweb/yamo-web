<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\EmailVerifyForm;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;

/**
 * Demander un code pour vérifier son adresse e-mail actuelle. Règles et libellés : ceux du formulaire `EmailVerifyForm` (rien
 * à saisir).
 */
class EmailVerifyRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route, et canal actif dans l'application.
     */
    public function authorize(): bool
    {
        return AuthChannels::email() && ($this->user()?->can('updateMe', User::class) ?? false);
    }

    protected function form(): EmailVerifyForm
    {
        return new EmailVerifyForm($this->user());
    }
}
