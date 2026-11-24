<?php

namespace Modules\Offer\Services;

use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Notifications\BookingNotification;

/**
 * Les notifications d'une demande, à chacune de ses étapes (décision 2026-11-19-notifications-reservations) : une
 * seule par action dans l'onglet, à l'autre partie. Celle qui agit la retrouve dans son fil sans la recevoir : elle
 * en est l'auteur (`user_id`, décision 2026-11-24-notifications-fil-de-groupe) ; elle en reçoit la confirmation par
 * e-mail et SMS (décision 2026-11-24-confirmation-auteur).
 */
class BookingNotifierService
{
    /** Une demande : le propriétaire est prévenu. */
    public function ownerOfRequest(Booking $booking): void
    {
        $booking->offer->user?->notify(BookingNotification::for($booking, BookingStepEnum::RECEIVED));
    }

    /**
     * Une demande modifiée : le propriétaire est prévenu (décision 2026-11-21-demande-modifiee-notification). Si l'étape
     * précédente a été réécrite sur place (`$rewritten`), l'onglet l'a déjà : seulement l'e-mail et le SMS.
     */
    public function ownerOfUpdate(Booking $booking, bool $edited, bool $rewritten = false): void
    {
        $booking->offer->user?->notify(BookingNotification::for($booking, BookingStepEnum::UPDATED, edited: $edited, inTab: ! $rewritten));
    }

    /** Une annulation : le propriétaire est prévenu. */
    public function ownerOfCancellation(Booking $booking): void
    {
        $booking->offer->user?->notify(BookingNotification::for($booking, BookingStepEnum::CANCELLED));
    }

    /**
     * Celui qui agit reçoit la confirmation de l'étape `$step` par e-mail et SMS, sans notification dans l'onglet : le
     * membre pour une demande, une modification ou une annulation, le propriétaire pour une réponse.
     */
    public function confirmToActor(Booking $booking, BookingStepEnum $step, bool $edited = false): void
    {
        $actor = $step->section() === 'received' ? $booking->user : $booking->offer->user;
        $actor?->notify(BookingNotification::for($booking, $step, edited: $edited, inTab: false));
    }

    /** Une réponse : le membre est prévenu, acceptée ou refusée. */
    public function guestOfAnswer(Booking $booking): void
    {
        $booking->user?->notify(BookingNotification::for($booking, BookingStepEnum::answer($booking->status)));
    }
}
