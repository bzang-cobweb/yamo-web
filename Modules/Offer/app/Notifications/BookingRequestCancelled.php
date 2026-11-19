<?php

namespace Modules\Offer\Notifications;

/**
 * Au propriétaire, le membre a annulé sa demande.
 */
class BookingRequestCancelled extends BookingNotification
{
    protected function key(): string
    {
        return 'cancelled';
    }

    protected function section(): string
    {
        return 'received';
    }
}
