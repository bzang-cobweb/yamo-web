<?php

namespace Modules\Offer\Notifications;

/**
 * Au membre, sa demande est acceptée.
 */
class BookingRequestAccepted extends BookingNotification
{
    protected function key(): string
    {
        return 'accepted';
    }

    protected function section(): string
    {
        return 'mine';
    }
}
