<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Notifications\BookingNotification;

/**
 * Une réponse : le membre est prévenu, acceptée ou refusée (décision
 * 2026-11-19-notifications-reservations).
 */
class NotifyGuestOfBookingAnswer
{
    public function handle(BookingAnswered $event): void
    {
        $booking = $event->booking;

        $booking->user->notify(BookingNotification::for($booking, BookingStepEnum::answer($booking->status)));
    }
}
