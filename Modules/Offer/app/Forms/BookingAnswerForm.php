<?php

namespace Modules\Offer\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Illuminate\Support\Facades\Gate;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Booking;

/**
 * La réponse du propriétaire à une demande (décision 2026-11-19-contrat-reservations),
 * ouverte depuis « Réservations reçues » : `?record=` est l'identifiant de la
 * demande. La demande se lit en tête (membre, dates, voyageurs, message), puis
 * Accepter ou Refuser, avec un message facultatif pour un refus. Envoyée à
 * `POST /api/v1/bookings/{booking}/answer`.
 */
class BookingAnswerForm extends Form
{
    public const ACCEPT = 'accept';

    public const REFUSE = 'refuse';

    private ?Booking $booking = null;

    public static function name(): string
    {
        return 'offer.booking.answer';
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
        $booking = $this->booking;
        $locale = app()->getLocale();

        return array_values(array_filter([
            // La demande, à lire (non envoyée).
            $booking === null ? null : Field::text('guest')
                ->label(__('offer::booking.answer.guest'))
                ->default((string) $booking->user->full_name)
                ->readonly(),
            $booking === null ? null : Field::text('dates')
                ->label(__('offer::booking.answer.dates'))
                ->default(implode(' · ', array_filter([
                    $booking->period($locale),
                    $booking->guests === null ? null : __('offer::booking.list.guests', ['count' => $booking->guests]),
                    $booking->formattedTotal($locale),
                ])))
                ->readonly(),
            $booking?->message === null ? null : Field::textarea('message')
                ->label(__('offer::booking.form.message'))
                ->default($booking->message)
                ->readonly(),
            Field::segmented('answer')
                ->label(__('offer::booking.answer.answer'))
                ->options([self::ACCEPT => __('offer::booking.answer.accept'), self::REFUSE => __('offer::booking.answer.refuse')])
                ->required(),
            Field::textarea('refusal_message')
                ->label(__('offer::booking.answer.refusal_message'))
                ->visibleWhen('answer', self::REFUSE)
                ->maxLength(Booking::MESSAGE_MAX_LENGTH),
            // Un message pour une acceptation aussi (décision 2026-11-22-accepter-avec-message).
            Field::textarea('acceptance_message')
                ->label(__('offer::booking.answer.acceptance_message'))
                ->visibleWhen('answer', self::ACCEPT)
                ->maxLength(Booking::MESSAGE_MAX_LENGTH),
            // Accepter aussi les autres visites du même jour (décision 2026-11-22-accepter-autres-visites).
            Field::checkbox('accept_other_visits')
                ->label(__('offer::booking.answer.accept_other_visits_short'))
                ->visibleWhen('answer', self::ACCEPT),
        ]));
    }

    public function title(): string
    {
        return __('offer::booking.answer.title');
    }

    public function subtitle(): ?string
    {
        if ($this->booking === null) {
            return null;
        }

        return app(DomainRegistry::class)->require()->itemTitle($this->booking->offer) ?? (string) $this->booking->offer->getTranslation('title', app()->getLocale());
    }

    public function submission(): ?Submission
    {
        return $this->booking === null ? null : Submission::toRoute(
            'api.v1.bookings.answer',
            SuccessAction::BACK,
            __('offer::booking.answer.sent'),
            parameters: ['booking' => $this->booking->getKey()],
        );
    }

    public function submitLabel(): string
    {
        return __('offer::booking.answer.submit');
    }
}
