<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Events\BookingUpdated;
use Modules\Offer\Services\BookingNotifier;

/**
 * Une demande : le propriétaire de l'offre est prévenu (décision
 * 2026-11-19-notifications-reservations) ; de même quand le membre la modifie (décision
 * 2026-11-21-demande-modifiee-notification).
 */
final class NotifyOwnerOfBookingRequest
{
    public function __construct(
        private readonly BookingNotifier $notifier,
    ) {}

    public function handle(BookingRequested|BookingUpdated $event): void
    {
        if ($event instanceof BookingUpdated) {
            $this->notifier->ownerOfUpdate($event->booking, $event->edited);

            return;
        }

        $this->notifier->ownerOfRequest($event->booking);
    }
}
