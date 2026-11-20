<?php

namespace Modules\Notification\Contracts;

/**
 * Une notification qui nomme qui agit (décision 2026-11-20-notification-user-id) :
 * le canal `database` enregistre son compte dans la colonne `user_id`.
 */
interface HasNotificationUser
{
    /** Le compte de qui agit, ou `null`. */
    public function notificationUserId(object $notifiable): ?int;
}
