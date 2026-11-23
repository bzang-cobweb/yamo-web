<?php

namespace Modules\Offer\Forms;

use Illuminate\Support\Facades\Gate;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Booking;
use Modules\Offer\Services\OfferAvailability;

/**
 * Accepter une demande, comme on la refuse (décision 2026-11-22-accepter-avec-message) : `?record=` est la demande.
 * Sous le titre, le membre et les dates, et l'avertissement si d'autres demandes en attente la chevauchent ; un
 * message facultatif pour le membre ; la réponse part à `POST /api/v1/bookings/{booking}/answer` (« accepter » déjà
 * choisi).
 */
class BookingAcceptForm extends Form
{
    private ?Booking $booking = null;

    public static function name(): string
    {
        return 'offer.booking.accept';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function forRecord(string $key): static
    {
        $booking = Booking::query()->with(['offer', 'user'])->whereKey($key)->first() ?? abort(404);
        Gate::forUser($this->user)->authorize('answer', $booking);
        $this->booking = $booking;

        return $this;
    }

    public function fields(): array
    {
        $visits = $this->booking === null ? 0 : app(OfferAvailability::class)->sameDayPendingVisits($this->booking)->count();

        return array_values(array_filter([
            Field::hidden('answer')->default(BookingAnswerForm::ACCEPT),
            Field::textarea('acceptance_message')
                ->label(__('offer::booking.answer.acceptance_message'))
                ->maxLength(Booking::MESSAGE_MAX_LENGTH),
            // D'autres visites en attente le même jour : les accepter aussi, décoché par défaut (décision 2026-11-22-accepter-autres-visites).
            $visits === 0 ? null : Field::checkbox('accept_other_visits')
                ->label(trans_choice('offer::booking.answer.accept_other_visits', $visits, ['count' => $visits]))
                ->default(false),
        ]));
    }

    public function title(): string
    {
        return __('offer::booking.profile.accept_title');
    }

    /** « Marie Curie · du 3 déc. 2026 au 6 déc. 2026 ». */
    public function subtitle(): ?string
    {
        return $this->booking === null ? null : $this->booking->user->full_name.' · '.$this->booking->period(app()->getLocale());
    }

    /**
     * L'avertissement, à part, sous le titre : « 2 autres demandes chevauchent ces dates et seront refusées. », « 2 autres
     * visites sont demandées ce jour-là. » (décisions 2026-11-22-accepter-nombre-chevauchements,
     * 2026-11-22-accepter-autres-visites et 2026-11-22-formulaire-note).
     */
    public function notice(): ?string
    {
        if ($this->booking === null) {
            return null;
        }

        $availability = app(OfferAvailability::class);
        $others = $availability->overlappingPending($this->booking)->count();
        $visits = $availability->sameDayPendingVisits($this->booking)->count();
        $warnings = array_filter([
            $others === 0 ? null : trans_choice('offer::booking.profile.accept_overlaps', $others, ['count' => $others]),
            $visits === 0 ? null : trans_choice('offer::booking.profile.accept_same_day_visits', $visits, ['count' => $visits]),
        ]);

        return $warnings === [] ? null : implode(' ', $warnings);
    }

    /** Le titre saisi du logement, sous le membre et les dates (décision 2026-11-22-formulaire-description). */
    public function description(): ?string
    {
        return $this->booking === null ? null : ((string) $this->booking->offer->getTranslation('title', app()->getLocale()) ?: null);
    }

    public function submission(): ?Submission
    {
        return $this->booking === null ? null : Submission::toRoute(
            'api.v1.bookings.answer',
            SuccessAction::BACK,
            __('offer::booking.profile.accepted_notice'),
            parameters: ['booking' => $this->booking->getKey()],
        );
    }

    public function submitLabel(): string
    {
        return __('offer::booking.profile.accept_submit');
    }
}
