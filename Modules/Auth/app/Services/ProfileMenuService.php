<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Enums\ProfileMenuKey;
use Modules\Auth\Models\User;
use Modules\Auth\Support\ProfileMenuLinks;

/**
 * Menu du profil d'un utilisateur, défini ici et non dans le back-office
 * (décision 2026-11-17-regions-alphabetiques-menu-en-code) : les liens de `MENU`,
 * dans cet ordre, sans ceux d'un canal désactivé, chacun avec son libellé traduit
 * et le nom du formulaire qu'il ouvre (`form`), que l'app affiche sans le
 * connaître d'avance (décision 2026-11-17-formulaires-profil-generiques).
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
        ProfileMenuKey::ADDRESSES,
    ];

    /**
     * Chaque lien porte soit `form`, soit `list` : une version de l'app qui ne
     * connaît pas les listes ignore un lien sans `form`.
     *
     * @return Collection<int, array{key: string, label: string, form?: string, list?: string, group: array{key: string, label: string}}>
     */
    public function for(User $user): Collection
    {
        $links = app(ProfileMenuLinks::class);

        return collect(self::MENU)
            ->filter(fn (ProfileMenuKey $key): bool => $key->isAvailable())
            ->map(fn (ProfileMenuKey $key): array => [
                'key' => $key->value,
                'label' => $key->labelFor($user),
                ...($key->form() !== null ? ['form' => $key->form()] : ['list' => $key->list()]),
                // Les liens d'Auth : le groupe « Compte » (décision 2026-11-19-contrat-reservations-logements-profil).
                'group' => $links->group(ProfileMenuLinks::ACCOUNT),
            ])
            // Puis les liens des autres modules (`ProfileMenuLinks`).
            ->concat($links->for($user))
            // Rangés par groupe, dans l'ordre déclaré ; l'ordre d'inscription dans chaque groupe.
            ->sortBy(fn (array $link, int $index): array => [$links->order($link['group']['key']), $index])
            ->values();
    }
}
