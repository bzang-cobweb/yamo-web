<?php

namespace Modules\Offer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Offer\Models\Booking;

/**
 * Le propriétaire a accepté ou refusé une demande. (décision 2026-11-19-notifications-reservations).
 */
class BookingAnswered
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Booking $booking) {}
}
