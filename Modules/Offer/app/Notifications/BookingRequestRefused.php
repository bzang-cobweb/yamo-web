<?php

namespace Modules\Offer\Notifications;

use Modules\Offer\Enums\BookingStepEnum;

/**
 * Une demande refusée, au membre ; sa trace, au propriétaire. (décision 2026-11-22-notification-par-action).
 */
final class BookingRequestRefused extends BookingNotification
{
    public static function step(): BookingStepEnum
    {
        return BookingStepEnum::REFUSED;
    }
}
