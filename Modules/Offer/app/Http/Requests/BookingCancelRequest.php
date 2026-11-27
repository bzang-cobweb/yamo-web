<?php

namespace Modules\Offer\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Offer\Forms\BookingCancelForm;
use Modules\Offer\Models\Booking;

/**
 * Une annulation, validée par le formulaire `offer.booking.cancel` : seul le message, obligatoire
 * (décision 2026-11-26-annulation-message-obligatoire), est pris en compte (décision 2026-11-21-annuler-depuis-le-detail).
 */
class BookingCancelRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): BookingCancelForm
    {
        $form = new BookingCancelForm($this->user());
        $booking = $this->route('booking');

        return $booking instanceof Booking ? $form->forRecord((string) $booking->getKey()) : $form;
    }
}
