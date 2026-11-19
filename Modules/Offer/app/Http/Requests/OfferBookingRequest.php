<?php

namespace Modules\Offer\Http\Requests;

use Modules\Form\Http\Requests\FormBackedRequest;
use Modules\Offer\Forms\OfferBookingForm;
use Modules\Offer\Models\Offer;

/**
 * Une demande de réservation ou de visite, validée par le formulaire
 * `offer.booking` de l'offre de la route (séjour ou visite, selon le domaine).
 */
class OfferBookingRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): OfferBookingForm
    {
        $form = new OfferBookingForm($this->user());
        $offer = $this->route('offer');

        return $offer instanceof Offer ? $form->forRecord((string) $offer->getKey()) : $form;
    }
}
