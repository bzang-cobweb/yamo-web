<?php

namespace Modules\Offer\Http\Requests;

use Modules\Form\Http\Requests\FormBackedRequest;
use Modules\Offer\Forms\BookingEditForm;
use Modules\Offer\Models\Booking;

/**
 * Une demande modifiée, validée par le formulaire `offer.booking.edit` de la demande de la route
 * (décision 2026-11-21-modifier-reservation).
 */
class BookingUpdateRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): BookingEditForm
    {
        $form = new BookingEditForm($this->user());
        $booking = $this->route('booking');

        return $booking instanceof Booking ? $form->forRecord((string) $booking->getKey()) : $form;
    }
}
