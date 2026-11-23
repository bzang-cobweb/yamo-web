<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Services\BookingNotifier;

/**
 * Une réponse : le membre est prévenu, acceptée ou refusée (décision
 * 2026-11-19-notifications-reservations).
 */
final class NotifyGuestOfBookingAnswer
{
    public function __construct(
        private readonly BookingNotifier $notifier,
    ) {}

    public function handle(BookingAnswered $event): void
    {
        $this->notifier->guestOfAnswer($event->booking);
    }
}
