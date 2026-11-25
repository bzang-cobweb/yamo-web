<?php

namespace Modules\Partner\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Partner\Http\Resources\PartnerResource;
use Modules\Partner\Lists\PartnerListing;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;

/**
 * Les partenaires de l'accueil, sans compte (décision 2026-11-23-module-partenaires).
 */
class PartnerController
{
    /**
     * Les partenaires visibles du pays courant, dans leur ordre ; chacun compte une vue.
     */
    /**
     * Les partenaires de l'accueil, et l'action « Devenir partenaire » de la dernière carte du carrousel : la même que
     * le pied de la page « Partenaires » (décision 2026-11-25-accueil-carte-devenir-partenaire).
     */
    public function index(Request $request, PartnerService $partnerService): AnonymousResourceCollection
    {
        return PartnerResource::collection($partnerService->shown($request->user('sanctum')))
            ->additional(['meta' => ['become' => (new PartnerListing($request->user('sanctum')))->footer()]]);
    }

    /**
     * Un partenaire visible ; 404 sinon (décision 2026-11-23-partenaires-liste-detail).
     */
    public function show(Request $request, Partner $partner): PartnerResource
    {
        // Compte facultatif (jeton Sanctum s'il y en a un) : ses comptes voient aussi un partenaire pas encore publié
        // (`PartnerPolicy::view`, décision 2026-11-24-partenaires-policy-routes).
        Gate::forUser($request->user('sanctum'))->authorize('view', $partner);

        // Sa page montre son e-mail et son téléphone (décision 2026-11-24-partenaire-contact).
        return (new PartnerResource($partner->load('media')))->withContact();
    }

    /**
     * Sa page ouverte, par n'importe quel lien : un clic de plus, sauf pour ses propres comptes (décisions
     * 2026-11-25-compteurs-partenaires et 2026-11-25-clic-a-l-ouverture). 404 s'il n'est pas visible.
     */
    public function click(Request $request, Partner $partner, PartnerService $partnerService): Response
    {
        Gate::forUser($request->user('sanctum'))->authorize('view', $partner);
        // Seul un partenaire affiché compte un clic.
        abort_unless($partnerService->click($partner, $request->user('sanctum')), 404);

        return response()->noContent();
    }
}
