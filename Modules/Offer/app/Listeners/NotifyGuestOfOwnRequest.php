<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Events\BookingRequested as BookingRequestedEvent;
use Modules\Offer\Notifications\BookingNotification;

/**
 * Sa demande, puis son annulation : le membre les retrouve dans son propre fil, déjà lues,
 * comme le propriétaire ses réponses ; sa carte de « Mes réservations » ouvre ce fil
 * (décision 2026-11-21-reservation-comme-notification).
 */
class NotifyGuestOfOwnRequest
{
    public function handle(BookingRequestedEvent|BookingCancelled $event): void
    {
        $booking = $event->booking;
        $step = $event instanceof BookingCancelled ? BookingStepEnum::CANCELLED : BookingStepEnum::RECEIVED;

        $booking->user?->notify(BookingNotification::for($booking, $step, own: true));
    }
}
