<?php

namespace Modules\Offer\Events;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Offer\Models\Booking;

/**
 * Le membre a modifié sa demande en attente (décision 2026-11-21-demande-modifiee-notification).
 */
class BookingUpdated
{
    use Dispatchable, SerializesModels;

    /** `$createdAt` : la date de l'étape du propriétaire qu'elle remplace (décision 2026-11-21-etape-modifiee-date). */
    public function __construct(public readonly Booking $booking, public readonly ?CarbonInterface $createdAt = null) {}
}
