<?php

namespace Modules\Offer\Forms;

use Illuminate\Support\Facades\Gate;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Submission;
use Modules\Offer\Domains\DomainRegistry;
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

    /** Le titre saisi du logement, comme pour « Accepter la demande » (décision 2026-11-23-modifier-annuler-comme-accepter). */
    public function title(): string
    {
        $title = $this->offer === null ? '' : (string) $this->offer->getTranslation('title', app()->getLocale());

        return $title !== '' ? $title : (string) $this->navigationTitle();
    }

    /** « Modifier la demande », dans la barre du haut (décision 2026-11-23-modifier-annuler-comme-accepter). */
    public function navigationTitle(): ?string
    {
        return __('offer::booking.form.edit_title');
    }

    /**
     * Le titre composé du logement (« Location meublée · Studio · Douala »), en sous-titre ; vaut aussi pour
     * l'annulation (décision 2026-11-23-modifier-annuler-titre-compose).
     */
    public function subtitle(): ?string
    {
        return $this->offer === null ? null : app(DomainRegistry::class)->require()->itemTypeLine($this->offer, app()->getLocale());
    }

    /** Le titre et le sous-titre centrés (décision 2026-11-23-modifier-annuler-titre-compose). */
    public function titleAlign(): string
    {
        return 'center';
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
