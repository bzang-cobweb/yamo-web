<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Notifications\BookingRequested as BookingRequestedNotification;

/**
 * Une demande : le propriétaire de l'offre est prévenu (décision
 * 2026-11-19-notifications-reservations).
 */
class NotifyOwnerOfBookingRequest
{
    public function handle(BookingRequested $event): void
    {
        $event->booking->offer->user?->notify(new BookingRequestedNotification($event->booking, BookingStepEnum::RECEIVED));
    }
}
