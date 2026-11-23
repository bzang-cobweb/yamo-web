<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Services\BookingNotifier;

/**
 * Une annulation : le propriétaire de l'offre est prévenu (décision
 * 2026-11-19-notifications-reservations).
 */
final class NotifyOwnerOfBookingCancellation
{
    public function __construct(
        private readonly BookingNotifier $notifier,
    ) {}

    public function handle(BookingCancelled $event): void
    {
        $this->notifier->ownerOfCancellation($event->booking);
    }
}
