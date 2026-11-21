<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Events\BookingUpdated;
use Modules\Offer\Notifications\BookingRequested as BookingRequestedNotification;

/**
 * Une demande : le propriétaire de l'offre est prévenu (décision
 * 2026-11-19-notifications-reservations) ; de même quand le membre la modifie (décision
 * 2026-11-21-demande-modifiee-notification).
 */
class NotifyOwnerOfBookingRequest
{
    public function handle(BookingRequested|BookingUpdated $event): void
    {
        $notification = $event instanceof BookingUpdated
            ? new BookingRequestedNotification($event->booking, BookingStepEnum::UPDATED, createdAt: $event->createdAt)
            : new BookingRequestedNotification($event->booking, BookingStepEnum::RECEIVED);

        $event->booking->offer->user?->notify($notification);
    }
}
