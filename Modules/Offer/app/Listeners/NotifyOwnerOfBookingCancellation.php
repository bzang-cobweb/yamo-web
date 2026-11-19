<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Notifications\BookingRequestCancelled;

/**
 * Une annulation : le propriétaire de l'offre est prévenu (décision
 * 2026-11-19-notifications-reservations).
 */
class NotifyOwnerOfBookingCancellation
{
    public function handle(BookingCancelled $event): void
    {
        $event->booking->offer->user?->notify(new BookingRequestCancelled($event->booking));
    }
}
