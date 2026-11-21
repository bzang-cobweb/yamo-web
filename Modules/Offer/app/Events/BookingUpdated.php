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

    /** `$edited` : son message diffère du précédent (décision 2026-11-21-etape-modifiee-date). */
    public function __construct(public readonly Booking $booking, public readonly bool $edited = false) {}
}
