<?php

namespace Modules\Offer\Services;

use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Notifications\BookingNotification;

/**
 * Les notifications d'une demande, à chacune de ses étapes (décision 2026-11-19-notifications-reservations) :
 * l'autre partie est prévenue, et qui agit retrouve son étape dans son propre fil, déjà lue (décisions
 * 2026-11-20-reponse-dans-son-fil et 2026-11-21-reservation-comme-notification).
 */
class BookingNotifier
{
    /** Une demande : le propriétaire est prévenu. */
    public function ownerOfRequest(Booking $booking): void
    {
        $booking->offer->user?->notify(BookingNotification::for($booking, BookingStepEnum::RECEIVED));
    }

    /** Une demande modifiée : le propriétaire est prévenu (décision 2026-11-21-demande-modifiee-notification). */
    public function ownerOfUpdate(Booking $booking, bool $edited): void
    {
        $booking->offer->user?->notify(BookingNotification::for($booking, BookingStepEnum::UPDATED, edited: $edited));
    }

    /** Une annulation : le propriétaire est prévenu. */
    public function ownerOfCancellation(Booking $booking): void
    {
        $booking->offer->user?->notify(BookingNotification::for($booking, BookingStepEnum::CANCELLED));
    }

    /** Une réponse : le propriétaire la retrouve dans son propre fil. */
    public function ownerOfOwnAnswer(Booking $booking): void
    {
        $booking->offer->user?->notify(BookingNotification::for($booking, BookingStepEnum::answer($booking->status), own: true));
    }

    /** Une réponse : le membre est prévenu, acceptée ou refusée. */
    public function guestOfAnswer(Booking $booking): void
    {
        $booking->user?->notify(BookingNotification::for($booking, BookingStepEnum::answer($booking->status)));
    }

    /** Sa demande : le membre la retrouve dans son propre fil. */
    public function guestOfOwnRequest(Booking $booking): void
    {
        $booking->user?->notify(BookingNotification::for($booking, BookingStepEnum::RECEIVED, own: true));
    }

    /** Son annulation : le membre la retrouve dans son propre fil. */
    public function guestOfOwnCancellation(Booking $booking): void
    {
        $booking->user?->notify(BookingNotification::for($booking, BookingStepEnum::CANCELLED, own: true));
    }
}
