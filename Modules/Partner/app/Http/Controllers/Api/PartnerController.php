<?php

namespace Modules\Partner\Http\Controllers\Api;

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
     * Un toucher sur un partenaire : un clic de plus. 404 s'il n'est pas visible.
     */
    public function click(Partner $partner, PartnerService $partnerService): Response
    {
        abort_unless($partnerService->click($partner), 404);

        return response()->noContent();
    }
}
