<?php

namespace Modules\Offer\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Offer\Forms\OfferReviewForm;

/**
 * Son avis sur une offre, validé par le formulaire `offer.review` : note de 1 à 5
 * et commentaire, tous deux obligatoires.
 */
class OfferReviewRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): OfferReviewForm
    {
        return new OfferReviewForm($this->user());
    }
}
