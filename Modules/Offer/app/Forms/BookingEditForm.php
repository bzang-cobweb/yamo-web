<?php

namespace Modules\Offer\Forms;

use Illuminate\Support\Facades\Gate;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Submission;
use Modules\Offer\Models\Booking;

/**
 * Modifier sa demande tant qu'elle est en attente (décision 2026-11-21-modifier-reservation) :
 * les champs de la demande (`OfferBookingForm`), déjà remplis, `?record=` étant l'identifiant de
 * la demande. Envoyé à `PUT /api/v1/bookings/{booking}`.
 */
class BookingEditForm extends OfferBookingForm
{
    public static function name(): string
    {
        return 'offer.booking.edit';
    }

    public function forRecord(string $key): static
    {
        $booking = Booking::query()->with('offer')->whereKey($key)->first() ?? abort(404);
        Gate::forUser($this->user)->authorize('edit', $booking);
        $this->editing = $booking;
        $this->offer = $booking->offer;

        return $this;
    }

    /**
     * Les champs de la demande, remplis de ses valeurs.
     */
    public function fields(): array
    {
        $booking = $this->editing;
        if ($booking === null) {
            return parent::fields();
        }

        $values = [
            'check_in' => $booking->check_in->toDateString(),
            'check_out' => $booking->check_out?->toDateString(),
            'guests' => $booking->guests === null ? null : (string) $booking->guests,
            'message' => $booking->message,
        ];

        return array_map(fn (Field $field): Field => array_key_exists($field->name, $values) ? $field->default($values[$field->name]) : $field, parent::fields());
    }

    /**
     * « Modifier la demande », sous le titre de l'offre (décision 2026-11-22-formulaire-demande-titre-offre).
     */
    protected function action(): string
    {
        return __('offer::booking.form.edit_title');
    }

    /** « Enregistrer », pas « Envoyer la demande » (décision 2026-11-21-modifier-reservation). */
    public function submitLabel(): string
    {
        return __('offer::booking.form.save');
    }

    public function submission(): ?Submission
    {
        return $this->editing === null ? null : Submission::toRoute(
            'api.v1.bookings.update',
            SuccessAction::BACK,
            __('offer::booking.form.updated'),
            parameters: ['booking' => $this->editing->getKey()],
        );
    }
}
