<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Models\ProfileMenuLink;
use Modules\Auth\Models\User;

/**
 * Menu du profil d'un utilisateur : les liens actifs du back-office, dans leur
 * ordre, sans ceux d'un canal désactivé, chacun avec son libellé traduit
 * (décision 2026-11-17-tri-regions-menu-profil).
 */
class ProfileMenuService
{
    /**
     * @return Collection<int, array{key: string, label: string}>
     */
    public function for(User $user): Collection
    {
        return ProfileMenuLink::query()->active()->ordered()->get()
            ->filter(fn (ProfileMenuLink $link): bool => $link->key->isAvailable())
            ->map(fn (ProfileMenuLink $link): array => [
                'key' => $link->key->value,
                'label' => $link->key->labelFor($user),
            ])
            ->values();
    }
}
