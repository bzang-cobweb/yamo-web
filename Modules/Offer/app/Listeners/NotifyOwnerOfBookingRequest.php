<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Events\BookingUpdated;
use Modules\Offer\Services\BookingNotifierService;

/**
 * Une demande : le propriétaire de l'offre est prévenu (décision
 * 2026-11-19-notifications-reservations) ; de même quand le membre la modifie (décision
 * 2026-11-21-demande-modifiee-notification).
 */
final class NotifyOwnerOfBookingRequest
{
    public function __construct(
        private readonly BookingNotifierService $bookingNotifierService,
    ) {}

    public function handle(BookingRequested|BookingUpdated $event): void
    {
        if ($event instanceof BookingUpdated) {
            $this->bookingNotifierService->ownerOfUpdate($event->booking, $event->edited, $event->rewritten);

            return;
        }

        $this->bookingNotifierService->ownerOfRequest($event->booking);
    }
}
