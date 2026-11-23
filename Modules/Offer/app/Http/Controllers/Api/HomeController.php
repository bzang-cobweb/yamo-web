<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Offer\Lists\OfferBrowseListing;

/**
 * L'accueil de l'app (décision 2026-11-23-accueil-carrousels-par-ville).
 */
class HomeController
{
    /**
     * Un carrousel par ville : 10 offres visibles de cette ville, au hasard, en cartes comme la recherche.
     * Compte facultatif (jeton Sanctum s'il y en a un), pour le cœur des favoris.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => [
            'carousels' => (new OfferBrowseListing($request->user('sanctum')))->cityCarousels(),
        ]]);
    }
}
