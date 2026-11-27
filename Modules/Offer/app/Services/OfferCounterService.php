<?php

namespace Modules\Offer\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Offer\Models\Offer;

/**
 * Les vues et les clics d'une offre, sur les mêmes critères que les partenaires (décision
 * 2026-11-25-compteurs-offres). Une vue : l'offre est servie dans un carrousel de l'accueil, le bloc « À la une » ou la
 * recherche. Un clic : sa fiche est ouverte, par n'importe quel lien (décision 2026-11-25-clic-a-l-ouverture). Rien pour
 * son propriétaire. Le clic d'un membre connecté range aussi l'offre dans ses « Récemment consultés ».
 */
class OfferCounterService
{
    /**
     * Une vue de plus pour chaque offre affichée, sauf celles de `$viewer`.
     *
     * @param  iterable<Model>  $offers
     */
    public function countViews(iterable $offers, ?Authenticatable $viewer): void
    {
        $viewerId = $viewer?->getAuthIdentifier();
        $ids = [];
        foreach ($offers as $offer) {
            /** @var Offer $offer */
            if ($viewerId !== null && (int) $offer->user_id === (int) $viewerId) {
                continue;
            }
            $ids[] = (int) $offer->getKey();
        }
        if ($ids === []) {
            return;
        }

        Offer::query()->whereKey($ids)->increment('views_count');
    }

    /**
     * La fiche ouverte : un clic de plus, sauf pour son propriétaire. Faux si l'offre n'est pas visible des autres.
     */
    public function click(Offer $offer, ?Authenticatable $viewer): bool
    {
        if (! $offer->isVisibleToOthers()) {
            return false;
        }
        if ($viewer !== null && (int) $offer->user_id === (int) $viewer->getAuthIdentifier()) {
            return true;
        }

        $offer->increment('clicks_count');
        // Un membre connecté : l'offre entre en tête de ses « Récemment consultés » (décision 2026-11-27-listes-de-favoris).
        if ($viewer !== null) {
            app(WishlistService::class)->recordView($viewer, $offer);
        }

        return true;
    }
}
