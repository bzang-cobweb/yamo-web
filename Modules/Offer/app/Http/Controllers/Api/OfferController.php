<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Http\Requests\OfferRequest;
use Modules\Offer\Http\Resources\OfferDetailResource;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\OfferPhotoService;
use Modules\Offer\Services\OfferService;

/**
 * Les offres du compte dans le pays courant, selon le domaine actif (décisions
 * 2026-11-18-offres-et-domaines et 2026-11-18-espace-pays). Celle d'un autre
 * compte ou d'un autre pays répond 404. Les
 * réponses sont vides : l'app recharge la liste.
 */
class OfferController
{
    /**
     * La fiche d'une offre, ouverte sans connexion : publiée et du pays courant ;
     * le propriétaire connecté voit aussi la sienne en brouillon ou archivée.
     * Toute autre offre répond 404.
     *
     * @unauthenticated
     */
    public function show(Request $request, Offer $offer): OfferDetailResource
    {
        // Compte facultatif (jeton Sanctum s'il y en a un) : la route n'exige pas de connexion.
        Gate::forUser($request->user('sanctum'))->authorize('show', $offer);

        return new OfferDetailResource($offer->load(['offerAddress', 'address.country', 'category.parent', 'media']));
    }

    /**
     * Ajouter une offre : champs communs et attributs du domaine.
     */
    public function store(OfferRequest $request, OfferService $service, OfferPhotoService $photos, DomainRegistry $domains): Response
    {
        /** @var User $user */
        $user = $request->user();
        // L'offre et ses photos ensemble (décision 2026-11-19-contrat-champ-upload-photos).
        DB::transaction(function () use ($request, $service, $photos, $domains, $user): void {
            $offer = $service->create($user, $domains->require(), CurrentCountry::require(), $request->validated(), $user->preferredLocale());
            $photos->sync($offer, $user->getKey(), $request->validated('photos', []));
        });

        return response()->noContent();
    }

    /**
     * Modifier une de ses offres.
     */
    public function update(OfferRequest $request, Offer $offer, OfferService $service, OfferPhotoService $photos, DomainRegistry $domains): Response
    {
        /** @var User $user */
        $user = $request->user();
        // Saisie dans la langue du compte (décision 2026-11-18-offres-multilingues-categories-arbre).
        DB::transaction(function () use ($request, $offer, $service, $photos, $domains, $user): void {
            $service->update($offer, $domains->require(), $request->validated(), $user->preferredLocale());
            $photos->sync($offer, $user->getKey(), $request->validated('photos', []));
        });

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
