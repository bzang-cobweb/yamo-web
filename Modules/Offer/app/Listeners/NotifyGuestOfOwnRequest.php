<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Services\BookingNotifier;

/**
 * Sa demande, puis son annulation : le membre les retrouve dans son propre fil, déjà lues,
 * comme le propriétaire ses réponses ; sa carte de « Mes réservations » ouvre ce fil
 * (décision 2026-11-21-reservation-comme-notification).
 */
final class NotifyGuestOfOwnRequest
{
    public function __construct(
        private readonly BookingNotifier $notifier,
    ) {}

    public function handle(BookingRequested|BookingCancelled $event): void
    {
        if ($event instanceof BookingCancelled) {
            $this->notifier->guestOfOwnCancellation($event->booking);

            return;
        }

        $this->notifier->guestOfOwnRequest($event->booking);
    }
}
