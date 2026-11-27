<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Offer\Http\Requests\WishlistRenameRequest;
use Modules\Offer\Lists\OfferBrowseListing;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\Wishlist;
use Modules\Offer\Services\WishlistService;

/**
 * Ses listes de favoris (décision 2026-11-27-listes-de-favoris) : la renommer, la supprimer, en retirer une offre, et
 * le bloc « Récemment consultés » de l'accueil.
 */
class WishlistController
{
    /**
     * Le bloc « Récemment consultés » de l'accueil : son titre, l'écran de la liste entière et ses dernières offres
     * visibles, en cartes comme l'accueil ; `null` sans offre consultée.
     *
     * @return JsonResponse{data: array{title: string, link: string, items: list<array<string, mixed>>}|null}
     */
    public function recent(Request $request, WishlistService $wishlists): JsonResponse
    {
        $user = $request->user();
        $recent = $wishlists->recentList($user);
        $items = $recent === null ? [] : (new OfferBrowseListing($user))->recentOffers($recent);

        return response()->json(['data' => $items === [] || $recent === null ? null : [
            'title' => $recent->displayName(),
            'link' => '/wishlists/'.$recent->getKey(),
            'items' => $items,
        ]]);
    }

    /**
     * Renommer sa liste.
     */
    public function update(WishlistRenameRequest $request, Wishlist $wishlist): Response
    {
        $wishlist->update(['name' => trim((string) $request->validated('name'))]);

        return response()->noContent();
    }

    /**
     * Supprimer sa liste et ce qu'elle range (les offres restent).
     */
    public function destroy(Wishlist $wishlist): Response
    {
        $wishlist->delete();

        return response()->noContent();
    }

    /**
     * Retirer une offre de sa liste.
     */
    public function removeOffer(Wishlist $wishlist, Offer $offer, WishlistService $wishlists): Response
    {
        $wishlists->remove($wishlist, $offer);

        return response()->noContent();
    }
}
