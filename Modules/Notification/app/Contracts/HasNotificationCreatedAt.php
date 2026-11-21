<?php

namespace Modules\Notification\Contracts;

use Carbon\CarbonInterface;

/**
 * Une notification qui remplace une étape de son fil en garde la date (décision
 * 2026-11-21-etape-modifiee-date) : `created_at` reprend celle-ci, `updated_at` est
 * l'heure de l'envoi ; l'écart dit qu'elle a été modifiée.
 */
interface HasNotificationCreatedAt
{
    /** La date à enregistrer dans `created_at` ; `null` : maintenant. */
    public function notificationCreatedAt(object $notifiable): ?CarbonInterface;
}
