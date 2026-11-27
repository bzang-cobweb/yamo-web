<?php

namespace Modules\Partner\Http\Controllers\Api;

use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Partner\Http\Requests\PartnerMessageRequest;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerNotifierService;

/**
 * Écrire aux autres membres d'un partenaire (décision 2026-11-27-partenaire-messages) : le message entre dans son fil.
 * La réponse est vide : l'app ferme le panneau et relit la page.
 */
class PartnerMessageController
{
    public function store(PartnerMessageRequest $request, Partner $partner, PartnerNotifierService $notifier): Response
    {
        /** @var User $sender */
        $sender = $request->user();
        /** @var array{message: string} $data */
        $data = $request->validated();

        $notifier->message($partner, $sender, trim($data['message']));

        return response()->noContent();
    }
}
