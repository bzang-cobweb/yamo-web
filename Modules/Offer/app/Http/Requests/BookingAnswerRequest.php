<?php

namespace Modules\Offer\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Offer\Forms\BookingAnswerForm;
use Modules\Offer\Models\Booking;

/**
 * La réponse du propriétaire, validée par le formulaire `offer.booking.answer` :
 * accepter ou refuser, avec un message facultatif.
 */
class BookingAnswerRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): BookingAnswerForm
    {
        $form = new BookingAnswerForm($this->user());
        $booking = $this->route('booking');

        return $booking instanceof Booking ? $form->forRecord((string) $booking->getKey()) : $form;
    }
}
