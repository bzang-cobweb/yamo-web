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
     * Le bloc « À la une », puis un carrousel par région (décision 2026-11-25-region-de-l-adresse) : 10 offres visibles de cette ville, au hasard, en cartes comme la recherche.
     * Compte facultatif (jeton Sanctum s'il y en a un), pour le cœur des favoris.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => [
            // En haut de l'accueil, avant les villes (décision 2026-11-25-bloc-a-la-une).
            'featured' => (new OfferBrowseListing($request->user('sanctum')))->featuredOffers(),
            'carousels' => (new OfferBrowseListing($request->user('sanctum')))->regionCarousels(),
        ]]);
    }
}
