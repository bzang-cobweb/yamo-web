<?php

namespace Modules\Offer\Listeners;

use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Notification\Events\NotificationThreadSerializing;
use Modules\Offer\Models\Booking;
use Modules\Offer\Services\BookingThreadDetails;

/**
 * Sur la page d'une notification du fil d'une demande (`booking:12`), la demande et ce qu'en voit
 * le lecteur (décision 2026-11-20-notification-generique) : le travail est fait par `BookingThreadDetails`.
 */
final class AddBookingToNotificationThread
{
    public function __construct(
        private readonly BookingThreadDetails $details,
    ) {}

    public function handle(NotificationThreadSerializing $event): void
    {
        // 1. Réunir : le fil, son identifiant de demande, le lecteur.
        if (! str_starts_with($event->thread, Booking::THREAD_PREFIX)) {
            return;
        }
        $id = Str::after($event->thread, Booking::THREAD_PREFIX);
        $reader = request()->user();

        // 2. Appeler le service ; un identifiant illisible vaut une demande supprimée.
        $event->data = $this->details->forThread(ctype_digit($id) ? (int) $id : null, $event->data, $reader instanceof User ? $reader : null);
    }
}
