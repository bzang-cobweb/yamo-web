<?php

namespace Modules\Partner\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Partner\Http\Requests\PartnerMemberRequest;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;

/**
 * Les membres d'un partenaire, depuis l'app (décision 2026-11-27-membre-administrateur) : un administrateur fait ou
 * défait un autre administrateur, ou retire un autre compte, jamais lui-même. La réponse est vide : l'app relit le partenaire.
 */
class PartnerMemberController
{
    public function update(PartnerMemberRequest $request, Partner $partner, User $user, PartnerService $partnerService): Response
    {
        /** @var User $actor */
        $actor = $request->user();
        $partnerService->setAdmin($partner, $user, $request->boolean('is_admin'), $actor);

        return response()->noContent();
    }

    /** Retirer un autre compte du partenaire (décision 2026-11-27-membre-retirer). */
    public function destroy(Request $request, Partner $partner, User $user, PartnerService $partnerService): Response
    {
        /** @var User $actor */
        $actor = $request->user();
        $partnerService->removeMember($partner, $user, $actor);

        return response()->noContent();
    }
}
