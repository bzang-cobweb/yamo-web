<?php

namespace Modules\Offer\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Offer\Http\Requests\OfferReviewRequest;
use Modules\Offer\Http\Resources\OfferReviewPageResource;
use Modules\Offer\Models\Offer;

/**
 * Son avis sur une offre (décision 2026-11-19-contrat-avis-offres) : publiée, du
 * pays courant (sinon 404), et pas la sienne (403). Un seul avis par membre et
 * par offre. Les réponses sont vides : l'app recharge la fiche.
 */
class OfferReviewController
{
    /** Avis par page de la liste complète. */
    public const PER_PAGE = 20;

    /**
     * Tous les avis d'une offre publiée, du plus récent au plus ancien, par pages
     * (décision 2026-11-19-nuit-tous-les-avis). Ouvert sans connexion, avec les
     * règles de la fiche ; une offre non publiée n'a pas d'avis : 404.
     *
     * @unauthenticated
     */
    #[QueryParameter('cursor', description: 'Curseur de la page voulue, tel que servi dans `next_cursor` ; absent : première page.', type: 'string', required: false)]
    public function index(Request $request, Offer $offer): OfferReviewPageResource
    {
        Gate::forUser($request->user('sanctum'))->authorize('show', $offer);
        abort_unless($offer->showsReviews(), 404);

        return new OfferReviewPageResource(
            $offer->reviews()->with('user')->orderByDesc('updated_at')->orderByDesc('id')->cursorPaginate(self::PER_PAGE),
        )->forOffer($offer);
    }

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
        // Un par un, pour que l'offre mette sa note à jour (décision 2026-11-25-accueil-a-grande-echelle).
        $offer->reviews()->where('user_id', $request->user()?->getAuthIdentifier())->get()->each->delete();

        return response()->noContent();
    }
}
