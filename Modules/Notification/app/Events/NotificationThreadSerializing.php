<?php

namespace Modules\Notification\Events;

use Illuminate\Notifications\DatabaseNotification;

/**
 * La page d'une notification qui appartient à un fil est en train d'être servie
 * (décision 2026-11-20-notification-generique) : un module peut compléter `$data`, la
 * réponse de l'API, avec ce que son fil désigne (`booking:12` → la demande). Le module
 * Notification ne connaît aucun fil en particulier.
 */
class NotificationThreadSerializing
{
    /**
     * @param  array<string, mixed>  $data  la réponse, modifiable par les écouteurs
     */
    public function __construct(
        public readonly DatabaseNotification $notification,
        public readonly string $thread,
        public array $data,
    ) {}
}
