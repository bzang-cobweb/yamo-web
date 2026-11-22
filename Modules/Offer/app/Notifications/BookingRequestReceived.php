<?php

namespace Modules\Offer\Notifications;

use Modules\Offer\Enums\BookingStepEnum;

/**
 * Une nouvelle demande, au propriétaire ; sa copie, au membre. (décision 2026-11-22-notification-par-action).
 */
final class BookingRequestReceived extends BookingNotification
{
    public static function step(): BookingStepEnum
    {
        return BookingStepEnum::RECEIVED;
    }
}
