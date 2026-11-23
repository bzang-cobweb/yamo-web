<?php

namespace Modules\Offer\Forms;

use Illuminate\Support\Facades\Gate;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Submission;
use Modules\Offer\Models\Booking;

/**
 * Annuler sa demande depuis sa page (décision 2026-11-21-annuler-depuis-le-detail) : les champs
 * de « Modifier la demande », remplis et en lecture seule, sauf le message, vide, joint à
 * l'annulation. Bouton « Confirmer ». Envoyé à `POST /api/v1/bookings/{booking}/cancel`.
 */
class BookingCancelForm extends BookingEditForm
{
    public static function name(): string
    {
        return 'offer.booking.cancel';
    }

    public function forRecord(string $key): static
    {
        $booking = Booking::query()->with('offer')->whereKey($key)->first() ?? abort(404);
        Gate::forUser($this->user)->authorize('cancel', $booking);
        $this->editing = $booking;
        $this->offer = $booking->offer;

        return $this;
    }

    /**
     * Les champs de la demande en lecture seule ; le message, à écrire, pour le propriétaire.
     */
    public function fields(): array
    {
        return array_map(fn (Field $field): Field => $field->name === 'message'
            ? $field->default(null)->label(__('offer::booking.cancel.message'))->placeholder(__('offer::booking.cancel.message_placeholder'))
            : $field->readonly(), parent::fields());
    }

    /** « Annuler la demande », dans la barre du haut ; le titre du logement en tête (décision 2026-11-23-modifier-annuler-comme-accepter). */
    public function navigationTitle(): ?string
    {
        return __('offer::booking.cancel.label');
    }

    public function submitLabel(): string
    {
        return __('offer::booking.cancel.submit');
    }

    public function submission(): ?Submission
    {
        return $this->editing === null ? null : Submission::toRoute(
            'api.v1.bookings.cancel',
            SuccessAction::BACK,
            __('offer::booking.cancel.done'),
            destructive: true,
            parameters: ['booking' => $this->editing->getKey()],
        );
    }
}
