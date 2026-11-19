<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Notifications\BookingRequestReceived;

/**
 * Une demande : le propriétaire de l'offre est prévenu (décision
 * 2026-11-19-notifications-reservations).
 */
class NotifyOwnerOfBookingRequest
{
    public function handle(BookingRequested $event): void
    {
        $event->booking->offer->user?->notify(new BookingRequestReceived($event->booking));
    }
}
