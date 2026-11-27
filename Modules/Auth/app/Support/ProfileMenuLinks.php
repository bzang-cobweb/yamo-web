<?php

namespace Modules\Auth\Support;

use Closure;
use Modules\Auth\Models\User;

/**
 * Liens du menu du profil ajoutés par d'autres modules (ex. « Mes logements » du
 * module Offer), placés après ceux d'Auth (`ProfileMenuService::MENU`). Auth ne
 * connaît pas ces modules : ce sont eux qui s'inscrivent ici (décision
 * 2026-11-18-offres-et-domaines).
 *
 * Le menu est rangé en groupes titrés (décision
 * 2026-11-19-contrat-reservations-logements-profil) : chaque module déclare les
 * siens (`addGroup`, avec leur rang) et range ses liens dedans ; Auth déclare
 * « Compte » (`ACCOUNT`), en tête.
 */
final class ProfileMenuLinks
{
    /** Le groupe des liens d'Auth (informations, e-mail, mot de passe…). */
    public const ACCOUNT = 'account';

    /** @var array<string, array{label: Closure(): string, order: int}> */
    private array $groups = [];

    /** @var array<string, array{label: Closure(User): string, list: Closure(): ?string, available: Closure(User): bool, group: string, icon: array{ios: string, android: string}|null}> */
    private array $links = [];

    /**
     * Un groupe du menu : son titre, et son rang (les plus petits en tête).
     *
     * @param  Closure(): string  $label
     */
    public function addGroup(string $key, Closure $label, int $order): void
    {
        $this->groups[$key] = ['label' => $label, 'order' => $order];
    }

    /**
     * Un lien vers une liste : son libellé selon le compte, le nom de la liste,
     * s'il est proposé, et son groupe.
     *
     * @param  Closure(User): string  $label
     * @param  Closure(): ?string  $list
     * @param  (Closure(User): bool)|null  $available  proposé à ce compte ?
     * @param  array{ios: string, android: string}|null  $icon  le symbole devant le libellé : SF Symbols (iOS), Material
     *                                                          (Android) — décision 2026-11-27-menu-profil-icones-sous-menus
     */
    public function addList(string $key, Closure $label, Closure $list, ?Closure $available = null, string $group = self::ACCOUNT, ?array $icon = null): void
    {
        $this->links[$key] = ['label' => $label, 'list' => $list, 'available' => $available ?? fn (): bool => true, 'group' => $group, 'icon' => $icon];
    }

    /**
     * Le groupe `$key` tel que servi : sa clé et son titre ; un groupe non déclaré
     * prend sa clé pour titre.
     *
     * @return array{key: string, label: string}
     */
    public function group(string $key): array
    {
        return ['key' => $key, 'label' => isset($this->groups[$key]) ? ($this->groups[$key]['label'])() : $key];
    }

    /**
     * Le rang d'un groupe ; non déclaré : à la fin.
     */
    public function order(string $key): int
    {
        return $this->groups[$key]['order'] ?? PHP_INT_MAX;
    }

    /**
     * Les liens proposés au compte, dans l'ordre d'inscription.
     *
     * @return list<array{key: string, label: string, icon: array{ios: string, android: string}|null, list: string, group: array{key: string, label: string}}>
     */
    public function for(User $user): array
    {
        $links = [];

        foreach ($this->links as $key => $link) {
            $list = ($link['list'])();

            if ($list !== null && ($link['available'])($user)) {
                $links[] = ['key' => $key, 'label' => ($link['label'])($user), 'icon' => $link['icon'], 'list' => $list, 'group' => $this->group($link['group'])];
            }
        }

        return $links;
    }
}
