<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Enums\ProfileMenuKey;
use Modules\Auth\Models\User;

/**
 * Menu du profil d'un utilisateur, défini ici et non dans le back-office
 * (décision 2026-11-17-regions-alphabetiques-menu-en-code) : les liens de `MENU`,
 * dans cet ordre, sans ceux d'un canal désactivé, chacun avec son libellé traduit.
 */
class ProfileMenuService
{
    /**
     * Les liens du menu, dans l'ordre affiché. Retirer une ligne masque le lien.
     *
     * @var list<ProfileMenuKey>
     */
    public const MENU = [
        ProfileMenuKey::PERSONAL,
        ProfileMenuKey::EMAIL,
        ProfileMenuKey::PHONE,
        ProfileMenuKey::PASSWORD,
        ProfileMenuKey::PREFERENCES,
    ];

    /**
     * @return Collection<int, array{key: string, label: string}>
     */
    public function for(User $user): Collection
    {
        return collect(self::MENU)
            ->filter(fn (ProfileMenuKey $key): bool => $key->isAvailable())
            ->map(fn (ProfileMenuKey $key): array => [
                'key' => $key->value,
                'label' => $key->labelFor($user),
            ])
            ->values();
    }
}
