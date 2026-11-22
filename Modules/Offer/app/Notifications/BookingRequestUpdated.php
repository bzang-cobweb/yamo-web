<?php

namespace Modules\Offer\Notifications;

use Modules\Offer\Enums\BookingStepEnum;

/**
 * Une demande modifiée par le membre, au propriétaire ; sa copie, au membre. (décision 2026-11-22-notification-par-action).
 */
final class BookingRequestUpdated extends BookingNotification
{
    public static function step(): BookingStepEnum
    {
        return BookingStepEnum::UPDATED;
    }
}
