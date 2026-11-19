<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Notifications\BookingRequestAccepted;
use Modules\Offer\Notifications\BookingRequestRefused;

/**
 * Une réponse : le membre est prévenu, acceptée ou refusée (décision
 * 2026-11-19-notifications-reservations).
 */
class NotifyGuestOfBookingAnswer
{
    public function handle(BookingAnswered $event): void
    {
        $booking = $event->booking;

        $booking->user->notify($booking->status === BookingStatusEnum::ACCEPTED
            ? new BookingRequestAccepted($booking)
            : new BookingRequestRefused($booking));
    }
}
