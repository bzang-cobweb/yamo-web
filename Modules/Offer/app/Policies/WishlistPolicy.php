<?php

namespace Modules\Offer\Policies;

use Illuminate\Auth\Access\Response;
use Modules\Auth\Models\User;
use Modules\Offer\Models\Wishlist;

/**
 * Les listes de favoris (décision 2026-11-27-listes-de-favoris) : seulement les siennes, jamais celles d'un autre, pas
 * même pour le super administrateur (elles n'ont pas de back-office). Une liste d'un autre répond 404, comme si elle
 * n'existait pas. « Récemment consultés » ne se renomme ni ne se supprime ; on en retire une offre.
 */
class WishlistPolicy
{
    /** « Récemment consultés » de l'accueil : tout compte connecté, pour ses propres offres consultées. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Renommer : sa liste nommée. */
    public function update(User $user, Wishlist $wishlist): Response
    {
        if (! $this->owns($user, $wishlist)) {
            return Response::denyAsNotFound();
        }

        return $wishlist->isRecent() ? Response::deny() : Response::allow();
    }

    /** Supprimer : sa liste nommée. */
    public function delete(User $user, Wishlist $wishlist): Response
    {
        return $this->update($user, $wishlist);
    }

    /** Retirer une offre : de n'importe laquelle de ses listes, « Récemment consultés » comprise. */
    public function removeOffer(User $user, Wishlist $wishlist): Response
    {
        return $this->owns($user, $wishlist) ? Response::allow() : Response::denyAsNotFound();
    }

    private function owns(User $user, Wishlist $wishlist): bool
    {
        return (int) $wishlist->user_id === (int) $user->getKey();
    }
}
