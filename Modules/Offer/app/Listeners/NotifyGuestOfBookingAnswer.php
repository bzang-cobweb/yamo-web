<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Services\BookingNotifierService;

/**
 * Une réponse : le membre est prévenu, acceptée ou refusée (décision
 * 2026-11-19-notifications-reservations).
 */
final class NotifyGuestOfBookingAnswer
{
    public function __construct(
        private readonly BookingNotifierService $bookingNotifierService,
    ) {}

    public function handle(BookingAnswered $event): void
    {
        $this->bookingNotifierService->guestOfAnswer($event->booking);
    }
}
