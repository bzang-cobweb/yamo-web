<?php

namespace Modules\Auth\Support;

use Closure;
use Modules\Auth\Models\User;

/**
 * Liens du menu du profil ajoutés par d'autres modules (ex. « Mes logements » du
 * module Offer), placés après ceux d'Auth (`ProfileMenuService::MENU`). Auth ne
 * connaît pas ces modules : ce sont eux qui s'inscrivent ici (décision
 * 2026-11-18-offres-et-domaines).
 */
final class ProfileMenuLinks
{
    /** @var array<string, array{label: Closure(User): string, list: Closure(): ?string, available: Closure(User): bool}> */
    private array $links = [];

    /**
     * Un lien vers une liste : son libellé selon le compte, le nom de la liste,
     * et s'il est proposé.
     *
     * @param  Closure(User): string  $label
     * @param  Closure(): ?string  $list
     * @param  (Closure(User): bool)|null  $available  proposé à ce compte ?
     */
    public function addList(string $key, Closure $label, Closure $list, ?Closure $available = null): void
    {
        $this->links[$key] = ['label' => $label, 'list' => $list, 'available' => $available ?? fn (): bool => true];
    }

    /**
     * Les liens proposés au compte, dans l'ordre d'inscription.
     *
     * @return list<array{key: string, label: string, list: string}>
     */
    public function for(User $user): array
    {
        $links = [];

        foreach ($this->links as $key => $link) {
            $list = ($link['list'])();

            // La disponibilité peut dépendre du compte (« Réservations reçues » : qui a des offres).
            if ($list !== null && ($link['available'])($user)) {
                $links[] = ['key' => $key, 'label' => ($link['label'])($user), 'list' => $list];
            }
        }

        return $links;
    }
}
