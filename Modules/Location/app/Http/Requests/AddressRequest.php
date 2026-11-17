<?php

namespace Modules\Location\Http\Requests;

use Modules\Form\Http\Requests\FormBackedRequest;
use Modules\Location\Forms\AddressForm;

/**
 * Ajout ou changement d'une adresse du compte, validé par le formulaire
 * `location.address` : une seule source de règles. La route porte la garde de
 * `AddressPolicy`.
 */
class AddressRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): AddressForm
    {
        return new AddressForm($this->user());
    }
}
