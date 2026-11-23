<?php

namespace Modules\Offer\Forms;

use Illuminate\Support\Facades\Gate;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Booking;

/**
 * Refuser une demande, depuis sa carte (décision
 * 2026-11-19-contrat-reservations-logements-profil) : `?record=` est la demande. Un
 * message facultatif pour le membre ; la réponse part à
 * `POST /api/v1/bookings/{booking}/answer` (« refuser » déjà choisi).
 */
class BookingRefuseForm extends Form
{
    private ?Booking $booking = null;

    public static function name(): string
    {
        return 'offer.booking.refuse';
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
        return [
            Field::hidden('answer')->default(BookingAnswerForm::REFUSE),
            Field::textarea('refusal_message')
                ->label(__('offer::booking.answer.refusal_message'))
                ->maxLength(Booking::MESSAGE_MAX_LENGTH),
        ];
    }

    public function title(): string
    {
        return __('offer::booking.profile.refuse_title');
    }

    public function subtitle(): ?string
    {
        return $this->booking === null ? null : $this->booking->user->full_name.' · '.$this->booking->period(app()->getLocale());
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
            __('offer::booking.answer.refused'),
            parameters: ['booking' => $this->booking->getKey()],
        );
    }

    public function submitLabel(): string
    {
        return __('offer::booking.profile.refuse_submit');
    }
}
