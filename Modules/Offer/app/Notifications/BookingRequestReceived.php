<?php

namespace Modules\Offer\Notifications;

/**
 * Au propriétaire, une nouvelle demande, avec le message du membre.
 */
class BookingRequestReceived extends BookingNotification
{
    protected function key(): string
    {
        return 'requested';
    }

    protected function section(): string
    {
        return 'received';
    }

    protected function note(): ?string
    {
        return $this->booking->message;
    }
}
