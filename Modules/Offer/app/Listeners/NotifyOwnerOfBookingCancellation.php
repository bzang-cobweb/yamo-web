<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Notifications\BookingNotification;

/**
 * Une annulation : le propriétaire de l'offre est prévenu (décision
 * 2026-11-19-notifications-reservations).
 */
class NotifyOwnerOfBookingCancellation
{
    public function handle(BookingCancelled $event): void
    {
        $event->booking->offer->user?->notify(BookingNotification::for($event->booking, BookingStepEnum::CANCELLED));
    }
}
