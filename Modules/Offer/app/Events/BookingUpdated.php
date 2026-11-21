<?php

namespace Modules\Offer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Offer\Models\Booking;

/**
 * Le membre a modifié sa demande en attente (décision 2026-11-21-demande-modifiee-notification).
 */
class BookingUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Booking $booking) {}
}
