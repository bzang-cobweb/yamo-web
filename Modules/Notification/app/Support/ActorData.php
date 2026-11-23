<?php

namespace Modules\Notification\Support;

use App\Support\MediaUrl;
use Modules\Auth\Models\User;

/**
 * Qui agit, tel que l'app l'affiche en tête d'une carte (décision
 * 2026-11-19-carte-notification) : photo, nom, initiales, en ligne, sa page. Sans
 * coordonnées (décision 2026-11-20-sans-coordonnees).
 * Le même dans une notification et dans le calendrier d'une offre.
 */
final class ActorData
{
    /**
     * @return array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string}
     */
    public static function from(User $user): array
    {
        return [
            'id' => (string) $user->getKey(),
            'name' => (string) $user->full_name,
            'initials' => (string) $user->initials,
            'photo' => MediaUrl::onRequestHost($user->avatarUrl('list')),
            'online' => (bool) $user->is_online,
            'link' => '/members/'.$user->getKey(),
        ];
    }
}
