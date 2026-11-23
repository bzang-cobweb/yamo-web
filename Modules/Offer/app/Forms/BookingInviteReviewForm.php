<?php

namespace Modules\Offer\Forms;

use Illuminate\Support\Facades\Gate;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Form;
use Modules\Form\Support\Submission;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Booking;

/**
 * Inviter le membre à donner son avis, depuis la page d'une demande terminée (décision
 * 2026-11-22-inviter-avis-depuis-la-demande) : `?record=` est la demande. Rien à saisir : le membre et les dates sous
 * le titre, puis « Envoyer l'invitation », qui part à `POST /api/v1/bookings/{booking}/invite-review`.
 */
class BookingInviteReviewForm extends Form
{
    private ?Booking $booking = null;

    public static function name(): string
    {
        return 'offer.booking.invite-review';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function forRecord(string $key): static
    {
        $booking = Booking::query()->with(['offer', 'user'])->whereKey($key)->first() ?? abort(404);
        Gate::forUser($this->user)->authorize('inviteReview', $booking);
        $this->booking = $booking;

        return $this;
    }

    public function fields(): array
    {
        return [];
    }

    public function title(): string
    {
        return __('offer::booking.invite_review.title');
    }

    public function subtitle(): ?string
    {
        return $this->booking === null ? null : $this->booking->user->full_name.' · '.$this->booking->period(app()->getLocale());
    }

    public function submission(): ?Submission
    {
        return $this->booking === null ? null : Submission::toRoute(
            'api.v1.bookings.invite-review',
            SuccessAction::BACK,
            __('offer::booking.invite_review.sent'),
            parameters: ['booking' => $this->booking->getKey()],
        );
    }

    public function submitLabel(): string
    {
        return __('offer::booking.invite_review.submit');
    }
}
