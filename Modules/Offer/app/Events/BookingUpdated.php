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

    /**
     * `$edited` : son message diffère du précédent (décision 2026-11-21-etape-modifiee-date) ; `$rewritten` : la
     * notification de l'étape précédente a été réécrite sur place, dans l'onglet du propriétaire (décision
     * 2026-11-24-notifications-fil-de-groupe) ; il ne reste qu'à prévenir par e-mail et SMS.
     */
    public function __construct(public readonly Booking $booking, public readonly bool $edited = false, public readonly bool $rewritten = false) {}
}
