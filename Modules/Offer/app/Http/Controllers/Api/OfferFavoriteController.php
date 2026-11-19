<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Offer\Models\Offer;

/**
 * Ses favoris (décision 2026-11-19-contrat-vedette-favoris) : une offre publiée du
 * pays courant (sinon 404), pas la sienne (403). Ajouter ou retirer deux fois ne
 * change rien. Les réponses sont vides.
 */
class OfferFavoriteController
{
    /**
     * Mettre une offre en favori.
     */
    public function save(Request $request, Offer $offer): Response
    {
        $offer->favorites()->firstOrCreate(['user_id' => $request->user()?->getAuthIdentifier()]);

        return response()->noContent();
    }

    /**
     * Retirer une offre de ses favoris.
     */
    public function destroy(Request $request, Offer $offer): Response
    {
        $offer->favorites()->where('user_id', $request->user()?->getAuthIdentifier())->delete();

        return response()->noContent();
    }
}
