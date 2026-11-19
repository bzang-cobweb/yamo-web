<?php

namespace Modules\Offer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Offer\Models\Booking;

/**
 * Un membre demande un séjour ou une visite. (décision 2026-11-19-notifications-reservations).
 */
class BookingRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Booking $booking) {}
}
