<?php

namespace Modules\Notification\Contracts;

/**
 * Une notification qui appartient à un fil (décision 2026-11-20-suivi-demande) : le
 * canal `database` l'enregistre dans la colonne `thread`. Les notifications d'un même
 * fil ne font qu'une carte dans la liste, et la page de l'une montre tout le fil.
 */
interface HasNotificationThread
{
    /** La clé du fil (`booking:12`), ou `null` : la notification reste seule. */
    public function notificationThread(object $notifiable): ?string;
}
