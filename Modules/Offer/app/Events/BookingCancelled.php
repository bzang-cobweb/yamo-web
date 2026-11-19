<?php

namespace Modules\Offer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Offer\Models\Booking;

/**
 * Le membre a annulé sa demande. (décision 2026-11-19-notifications-reservations).
 */
class BookingCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Booking $booking) {}
}
