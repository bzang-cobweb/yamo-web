<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Offer\Http\Requests\OfferFavoriteRequest;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\Wishlist;
use Modules\Offer\Services\WishlistService;

/**
 * Le cœur d'une offre (décisions 2026-11-19-contrat-vedette-favoris et 2026-11-27-listes-de-favoris) : une offre publiée
 * du pays courant (sinon 404), pas la sienne (403).
 * - GET : le panneau du cœur vide, ses listes nommées (sans « Récemment consultés ») et ses textes ;
 * - PUT : la range dans une liste choisie (`wishlist`) ou créée (`name`) ; sans rien, dans sa première liste ;
 * - DELETE : la retire de toutes ses listes nommées (le cœur plein).
 * Ranger ou retirer deux fois ne change rien.
 */
class OfferFavoriteController
{
    /**
     * Ses listes, pour ranger l'offre : chacune avec son nom, son nombre d'offres et la photo de la dernière rangée.
     *
     * @return JsonResponse{data: array{title: string, create: array{label: string, placeholder: string, submit: string, max_length: int}, lists: list<array{id: string, name: string, subtitle: string, image: string|null}>}}
     */
    public function index(Request $request, Offer $offer, WishlistService $wishlists): JsonResponse
    {
        $lists = $wishlists->customLists($request->user());

        return response()->json(['data' => [
            'title' => __('offer::offer.wishlists.choose'),
            'create' => [
                'label' => __('offer::offer.wishlists.create'),
                'placeholder' => __('offer::offer.wishlists.name'),
                'submit' => __('offer::offer.wishlists.create_submit'),
                'max_length' => Wishlist::NAME_MAX_LENGTH,
            ],
            'lists' => $lists->map(fn (Wishlist $list): array => [
                'id' => (string) $list->getKey(),
                'name' => $list->displayName(),
                'subtitle' => trans_choice('offer::offer.wishlists.count', (int) $list->items_count, ['count' => (int) $list->items_count]),
                'image' => $wishlists->cover($list),
            ])->values()->all(),
        ]]);
    }

    /**
     * Ranger l'offre dans une liste.
     */
    public function save(OfferFavoriteRequest $request, Offer $offer, WishlistService $wishlists): Response
    {
        $wishlist = $request->validated('wishlist');
        $wishlists->add($request->user(), $offer, $wishlist === null ? null : (int) $wishlist, $request->validated('name'));

        return response()->noContent();
    }

    /**
     * Retirer l'offre de toutes ses listes nommées.
     */
    public function destroy(Request $request, Offer $offer, WishlistService $wishlists): Response
    {
        $wishlists->removeEverywhere($request->user(), $offer);

        return response()->noContent();
    }
}
