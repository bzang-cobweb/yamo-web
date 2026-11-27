<?php

namespace Modules\Auth\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Auth\Forms\PhoneVerifyForm;
use Modules\Auth\Models\User;
use Modules\Auth\Support\AuthChannels;

/**
 * Demander un code pour vérifier son numéro actuel. Règles et libellés : ceux du formulaire `PhoneVerifyForm` (rien
 * à saisir).
 */
class PhoneVerifyRequest extends FormBackedRequest
{
    /**
     * Même capacité que la garde de la route, et canal actif dans l'application.
     */
    public function authorize(): bool
    {
        return AuthChannels::phone() && ($this->user()?->can('updateMe', User::class) ?? false);
    }

    protected function form(): PhoneVerifyForm
    {
        return new PhoneVerifyForm($this->user());
    }
}
