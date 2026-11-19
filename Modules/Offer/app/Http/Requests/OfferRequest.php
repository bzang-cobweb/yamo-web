<?php

namespace Modules\Offer\Http\Requests;

use Modules\Form\Http\Requests\FormBackedRequest;
use Modules\Offer\Forms\OfferForm;
use Modules\Offer\Models\Offer;

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

    /**
     * Pour une modification, le formulaire de l'offre : ses photos enregistrées
     * sont acceptées dans la valeur du champ `photos`.
     */
    protected function form(): OfferForm
    {
        $form = new OfferForm($this->user());
        $offer = $this->route('offer');

        return $offer instanceof Offer ? $form->forRecord((string) $offer->getKey()) : $form;
    }
}
