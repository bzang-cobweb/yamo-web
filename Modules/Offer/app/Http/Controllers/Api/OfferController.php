<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Http\Requests\OfferRequest;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\OfferService;

/**
 * Les offres du compte, selon le domaine actif (décision
 * 2026-11-18-offres-et-domaines). Celle d'un autre compte répond 404. Les
 * réponses sont vides : l'app recharge la liste.
 */
class OfferController
{
    /**
     * Ajouter une offre : champs communs et attributs du domaine.
     */
    public function store(OfferRequest $request, OfferService $service, DomainRegistry $domains): Response
    {
        /** @var User $user */
        $user = $request->user();
        $service->create($user, $domains->require(), $request->validated(), $user->preferredLocale());

        return response()->noContent();
    }

    /**
     * Modifier une de ses offres.
     */
    public function update(OfferRequest $request, Offer $offer, OfferService $service, DomainRegistry $domains): Response
    {
        /** @var User $user */
        $user = $request->user();
        // Saisie dans la langue du compte (décision 2026-11-18-offres-multilingues-categories-arbre).
        $service->update($offer, $domains->require(), $request->validated(), $user->preferredLocale());

        return response()->noContent();
    }

    /**
     * Retirer une de ses offres (suppression douce).
     */
    public function destroy(Offer $offer, OfferService $service): Response
    {
        $service->delete($offer);

        return response()->noContent();
    }
}
