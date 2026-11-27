<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\User;
use Modules\Auth\Support\NotificationSettings;

/**
 * Les choix de notifications de l'utilisateur connecté (décision 2026-11-27-notifications-du-profil).
 */
final class NotificationSettingsService
{
    /**
     * @param  array<string, mixed>  $data  données validées
     */
    public function update(User $user, array $data): User
    {
        $user->forceFill(['notification_settings' => NotificationSettings::merge($user->notification_settings, $data)])->save();

        return $user;
    }
}
