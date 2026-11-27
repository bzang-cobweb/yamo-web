<?php

namespace Modules\Offer\Forms;

use Illuminate\Support\Facades\Gate;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Submission;
use Modules\Offer\Models\Booking;
use Modules\Offer\Notifications\BookingNotification;

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
     * Ni titre ni sous-titre dans le formulaire : le titre du panneau dit déjà l'action et le type de demande, et la page
     * de la demande montre le logement (décision 2026-11-27-modifier-annuler-sans-titre). Vaut aussi pour l'annulation.
     */
    public function title(): ?string
    {
        return null;
    }

    /**
     * « Modifier la demande de séjour » ou « de visite », dans la barre du haut (décisions
     * 2026-11-23-modifier-annuler-comme-accepter et 2026-11-26-formulaires-titre-type).
     */
    public function navigationTitle(): ?string
    {
        return __('offer::booking.form.edit_title', ['request' => BookingNotification::requestLabel($this->editing?->type)]);
    }

    public function subtitle(): ?string
    {
        return null;
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
            __('offer::booking.form.updated', ['request' => BookingNotification::requestLabel($this->editing->type)]),
            parameters: ['booking' => $this->editing->getKey()],
        );
    }
}
