<?php

namespace Modules\Auth\Http\Requests;

use Modules\Auth\Forms\UserAddressForm;
use Modules\Form\Http\Requests\FormBackedRequest;

/**
 * Ajout ou changement d'une adresse du compte (destinataire et adresse), validé
 * par le formulaire `auth.address` : une seule source de règles. La route
 * porte la garde de `UserAddressPolicy`.
 */
class UserAddressRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): UserAddressForm
    {
        return new UserAddressForm($this->user());
    }
}
