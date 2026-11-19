<?php

namespace Modules\Offer\Notifications;

/**
 * Au membre, sa demande est refusée, avec le motif s'il y en a un.
 */
class BookingRequestRefused extends BookingNotification
{
    protected function key(): string
    {
        return 'refused';
    }

    protected function section(): string
    {
        return 'mine';
    }

    protected function note(): ?string
    {
        return $this->booking->refusal_message;
    }
}
