<?php

namespace Modules\Offer\Listeners;

use Illuminate\Support\Str;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Offer\Http\Resources\BookingResource;
use Modules\Offer\Models\Booking;

/**
 * Sur la page d'une notification du fil d'une demande (`booking:12`), la demande en
 * léger, sous `booking` (décision 2026-11-20-notification-generique). Une demande
 * supprimée : `booking` vaut `null`.
 */
class AddBookingToNotificationThread
{
    public function handle(NotificationThreadSerializing $event): void
    {
        if (! str_starts_with($event->thread, Booking::THREAD_PREFIX)) {
            return;
        }

        $id = Str::after($event->thread, Booking::THREAD_PREFIX);
        $booking = ctype_digit($id) ? Booking::query()->find((int) $id) : null;

        $event->data['booking'] = $booking === null ? null : (new BookingResource($booking))->resolve(request());
    }
}
