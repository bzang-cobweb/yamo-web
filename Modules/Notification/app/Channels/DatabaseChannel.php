<?php

namespace Modules\Notification\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Notifications\Notification;
use Modules\Notification\Contracts\HasNotificationUser;

/**
 * Le canal `database` de Laravel, qui remplit aussi la colonne `user_id` (qui agit)
 * quand la notification la donne (décision 2026-11-20-notification-user-id).
 */
class DatabaseChannel extends BaseDatabaseChannel
{
    /**
     * @return array<string, mixed>
     */
    protected function buildPayload($notifiable, Notification $notification): array
    {
        return [
            ...parent::buildPayload($notifiable, $notification),
            'user_id' => $notification instanceof HasNotificationUser ? $notification->notificationUserId($notifiable) : null,
        ];
    }
}
