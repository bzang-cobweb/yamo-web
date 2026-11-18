<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Offer\Http\Requests\OfferReviewRequest;
use Modules\Offer\Models\Offer;

/**
 * Son avis sur une offre (décision 2026-11-19-contrat-avis-offres) : publiée, du
 * pays courant (sinon 404), et pas la sienne (403). Un seul avis par membre et
 * par offre. Les réponses sont vides : l'app recharge la fiche.
 */
class OfferReviewController
{
    /**
     * Donner son avis, ou remplacer celui qu'on a déjà donné.
     */
    public function save(OfferReviewRequest $request, Offer $offer): Response
    {
        /** @var User $user */
        $user = $request->user();
        /** @var array{rating: int|string, comment: string} $data */
        $data = $request->validated();

        $offer->reviews()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['rating' => (int) $data['rating'], 'comment' => trim($data['comment'])],
        );

        return response()->noContent();
    }

    /**
     * Retirer son avis ; sans avis, rien à faire (204 quand même).
     */
    public function destroy(Request $request, Offer $offer): Response
    {
        $offer->reviews()->where('user_id', $request->user()?->getAuthIdentifier())->delete();

        return response()->noContent();
    }
}
