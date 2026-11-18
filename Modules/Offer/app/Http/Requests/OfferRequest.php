<?php

namespace Modules\Offer\Http\Requests;

use Modules\Form\Http\Requests\FormBackedRequest;
use Modules\Offer\Forms\OfferForm;

/**
 * Ajout ou changement d'une offre, validé par le formulaire `offer.offer` :
 * champs communs et attributs du domaine actif, une seule source de règles.
 */
class OfferRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): OfferForm
    {
        return new OfferForm($this->user());
    }
}
