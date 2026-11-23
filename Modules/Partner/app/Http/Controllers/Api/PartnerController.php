<?php

namespace Modules\Partner\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\Partner\Http\Resources\PartnerResource;
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
    public function index(PartnerService $partnerService): AnonymousResourceCollection
    {
        return PartnerResource::collection($partnerService->shown());
    }

    /**
     * Un partenaire visible ; 404 sinon (décision 2026-11-23-partenaires-liste-detail).
     */
    public function show(Request $request, int $partner, PartnerService $partnerService): PartnerResource
    {
        // Compte facultatif (jeton Sanctum s'il y en a un) : ses comptes voient aussi un partenaire pas encore publié.
        // Sa page montre son e-mail et son téléphone (décision 2026-11-24-partenaire-contact).
        return (new PartnerResource($partnerService->find($partner, $request->user('sanctum')?->getAuthIdentifier()) ?? abort(404)))->withContact();
    }

    /**
     * Un toucher sur un partenaire : un clic de plus. 404 s'il n'est pas visible.
     */
    public function click(Partner $partner, PartnerService $partnerService): Response
    {
        abort_unless($partnerService->click($partner), 404);

        return response()->noContent();
    }
}
