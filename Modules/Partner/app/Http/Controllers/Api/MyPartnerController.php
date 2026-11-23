<?php

namespace Modules\Partner\Http\Controllers\Api;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Partner\Forms\PartnerForm;
use Modules\Partner\Http\Requests\PartnerRequest;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerLogoService;
use Modules\Partner\Services\PartnerService;

/**
 * « Mes partenaires » : proposer, modifier et retirer un partenaire depuis l'app (décision
 * 2026-11-24-partenaires-par-les-membres).
 */
class MyPartnerController
{
    public function store(PartnerRequest $request, PartnerService $partnerService, PartnerLogoService $partnerLogoService): Response
    {
        /** @var User $user */
        $user = $request->user();
        $form = new PartnerForm($user);

        DB::transaction(function () use ($request, $partnerService, $partnerLogoService, $user, $form): void {
            $partner = $partnerService->propose($user, $request->validated(), $form->locale());
            $partnerLogoService->sync($partner, $user->getKey(), $request->validated(PartnerLogoService::FIELD, []));
        });

        return response()->noContent();
    }

    public function update(PartnerRequest $request, Partner $partner, PartnerService $partnerService, PartnerLogoService $partnerLogoService): Response
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $partner, $partnerService, $partnerLogoService, $user): void {
            $partnerService->change($partner, $request->validated(), (new PartnerForm($user))->locale());
            $partnerLogoService->sync($partner, $user->getKey(), $request->validated(PartnerLogoService::FIELD, []));
        });

        return response()->noContent();
    }

    public function destroy(Partner $partner): Response
    {
        $partner->delete();

        return response()->noContent();
    }
}
