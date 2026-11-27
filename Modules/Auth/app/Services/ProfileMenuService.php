<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Enums\ProfileMenuKey;
use Modules\Auth\Models\User;
use Modules\Auth\Support\ProfileMenuLinks;

/**
 * Menu du profil d'un utilisateur, défini ici et non dans le back-office
 * (décision 2026-11-17-regions-alphabetiques-menu-en-code) : les liens de `MENU`,
 * dans cet ordre, sans ceux d'un canal désactivé, chacun avec son libellé traduit,
 * son icône et ce qu'il ouvre (`form`, `list` ou `menu`), que l'app affiche sans le
 * connaître d'avance (décisions 2026-11-17-formulaires-profil-generiques et
 * 2026-11-27-menu-profil-icones-sous-menus). Un sous-menu (`SUBMENUS`) a sa page.
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
        ProfileMenuKey::PRIVACY,
        ProfileMenuKey::PREFERENCES,
        ProfileMenuKey::ADDRESSES,
    ];

    /**
     * Les sous-menus : leur nom (la clé du lien qui les ouvre) et leurs liens, dans l'ordre.
     *
     * @var array<string, list<ProfileMenuKey>>
     */
    public const SUBMENUS = [
        // « Informations personnelles » : e-mail, mot de passe, identité y sont rangés (demande du 2026-11-27).
        'personal' => [
            ProfileMenuKey::DETAILS,
            ProfileMenuKey::EMAIL,
            ProfileMenuKey::PHONE,
            ProfileMenuKey::PASSWORD,
            ProfileMenuKey::IDENTITY,
        ],
        'privacy' => [
            ProfileMenuKey::DELETE_ACCOUNT,
        ],
    ];

    /**
     * Chaque lien porte `form`, `list` ou `menu` : une version de l'app qui ne
     * connaît pas une cible ignore le lien.
     *
     * @return Collection<int, array{key: string, label: string, icon: array{ios: string, android: string}|null, form?: string, list?: string, menu?: string, group: array{key: string, label: string}}>
     */
    public function for(User $user): Collection
    {
        $links = app(ProfileMenuLinks::class);

        return $this->links($user, self::MENU)
            // Puis les liens des autres modules (`ProfileMenuLinks`).
            ->concat($links->for($user))
            // Rangés par groupe, dans l'ordre déclaré ; l'ordre d'inscription dans chaque groupe.
            ->sortBy(fn (array $link, int $index): array => [$links->order($link['group']['key']), $index])
            ->values();
    }

    /**
     * Un sous-menu : son titre (le libellé du lien qui l'ouvre) et ses liens ; `null` s'il n'existe pas.
     *
     * @return array{title: string, data: list<array<string, mixed>>}|null
     */
    public function submenu(User $user, string $menu): ?array
    {
        $key = ProfileMenuKey::tryFrom($menu);

        if ($key === null || ! isset(self::SUBMENUS[$menu])) {
            return null;
        }

        return ['title' => $key->labelFor($user), 'data' => $this->links($user, self::SUBMENUS[$menu])->all()];
    }

    /**
     * @param  list<ProfileMenuKey>  $keys
     * @return Collection<int, array<string, mixed>>
     */
    private function links(User $user, array $keys): Collection
    {
        $group = app(ProfileMenuLinks::class)->group(ProfileMenuLinks::ACCOUNT);

        return collect($keys)
            ->filter(fn (ProfileMenuKey $key): bool => $key->isAvailable())
            ->map(fn (ProfileMenuKey $key): array => [
                'key' => $key->value,
                'label' => $key->labelFor($user),
                'icon' => $key->icon(),
                ...$key->target(),
                // Les liens d'Auth : le groupe « Compte » (décision 2026-11-19-contrat-reservations-logements-profil).
                'group' => $group,
            ])
            ->values();
    }
}
