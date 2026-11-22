<?php

namespace Modules\Offer\Notifications;

use Modules\Offer\Enums\BookingStepEnum;

/**
 * Une demande acceptée, au membre ; sa trace, au propriétaire. (décision 2026-11-22-notification-par-action).
 */
final class BookingRequestAccepted extends BookingNotification
{
    public static function step(): BookingStepEnum
    {
        return BookingStepEnum::ACCEPTED;
    }
}
