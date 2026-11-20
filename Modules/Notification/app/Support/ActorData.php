<?php

namespace Modules\Notification\Support;

use App\Support\MediaUrl;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;

/**
 * Qui agit, tel que l'app l'affiche en tête d'une carte (décision
 * 2026-11-19-carte-notification) : photo, nom, initiales, en ligne, sa page ; et,
 * sur demande, son e-mail et son téléphone (décision 2026-11-20-coordonnees-notification).
 * Le même dans une notification et dans le calendrier d'une offre.
 */
final class ActorData
{
    /**
     * @return array{id: string, name: string, initials: string, photo: string|null, online: bool, link: string, email: string|null, phone: string|null}
     */
    public static function from(User $user, bool $contact = false): array
    {
        return [
            'id' => (string) $user->getKey(),
            'name' => (string) $user->full_name,
            'initials' => (string) $user->initials,
            'photo' => MediaUrl::onRequestHost($user->avatarUrl()),
            'online' => (bool) $user->is_online,
            'link' => '/members/'.$user->getKey(),
            'email' => $contact ? $user->email : null,
            'phone' => $contact && $user->phone !== null ? PhoneNumbers::international($user->phone) : null,
        ];
    }
}
