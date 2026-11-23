<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Services\BookingNotifierService;

/**
 * Une annulation : le propriétaire de l'offre est prévenu (décision
 * 2026-11-19-notifications-reservations).
 */
final class NotifyOwnerOfBookingCancellation
{
    public function __construct(
        private readonly BookingNotifierService $bookingNotifierService,
    ) {}

    public function handle(BookingCancelled $event): void
    {
        $this->bookingNotifierService->ownerOfCancellation($event->booking);
    }
}
