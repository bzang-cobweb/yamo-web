<?php

namespace Modules\Offer\Listeners;

use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Notifications\BookingRequested;

/**
 * Une réponse : le propriétaire la retrouve dans son propre fil, déjà lue, et sa carte
 * remonte en tête (décision 2026-11-20-reponse-dans-son-fil).
 */
class NotifyOwnerOfOwnAnswer
{
    public function handle(BookingAnswered $event): void
    {
        $booking = $event->booking;

        $booking->offer->user?->notify(new BookingRequested($booking, BookingStepEnum::answer($booking->status), own: true));
    }
}
