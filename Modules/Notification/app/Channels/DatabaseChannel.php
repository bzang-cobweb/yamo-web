<?php

namespace Modules\Notification\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Notifications\Notification;
use Modules\Notification\Contracts\HasNotificationCreatedAt;
use Modules\Notification\Contracts\HasNotificationThread;
use Modules\Notification\Contracts\HasNotificationUser;
use Modules\Notification\Contracts\SentAsRead;

/**
 * Le canal `database` de Laravel, qui remplit aussi la colonne `user_id` (qui agit)
 * quand la notification la donne (décision 2026-11-20-notification-user-id), et la
 * colonne `thread` (son fil, décision 2026-11-20-suivi-demande), et l'enregistre lue
 * quand elle trace l'action de son destinataire (décision 2026-11-20-reponse-dans-son-fil).
 * Une étape qui en remplace une autre garde sa date de création (décision 2026-11-21-etape-modifiee-date).
 */
class DatabaseChannel extends BaseDatabaseChannel
{
    /**
     * @return array<string, mixed>
     */
    protected function buildPayload($notifiable, Notification $notification): array
    {
        $createdAt = $notification instanceof HasNotificationCreatedAt ? $notification->notificationCreatedAt($notifiable) : null;

        return [
            ...parent::buildPayload($notifiable, $notification),
            ...($createdAt === null ? [] : ['created_at' => $createdAt]),
            'user_id' => $notification instanceof HasNotificationUser ? $notification->notificationUserId($notifiable) : null,
            'thread' => $notification instanceof HasNotificationThread ? $notification->notificationThread($notifiable) : null,
            'read_at' => $notification instanceof SentAsRead && $notification->sentAsRead($notifiable) ? now() : null,
        ];
    }
}
