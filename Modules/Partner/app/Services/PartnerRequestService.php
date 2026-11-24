<?php

namespace Modules\Partner\Services;

use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerRequest;

/**
 * Les demandes d'un partenaire (décision 2026-11-24-partenaire-demandes).
 */
class PartnerRequestService
{
    /**
     * Un ajout (`$created`) ou une modification depuis l'app par `$userId` : la demande en validation du partenaire,
     * prolongée, ou une nouvelle.
     */
    public function submit(Partner $partner, int|string $userId, bool $created): PartnerRequest
    {
        $open = PartnerRequest::query()->where('partner_id', $partner->getKey())->open()->latest('id')->first();
        if ($open !== null) {
            $open->touch();

            return $open;
        }

        return PartnerRequest::query()->create([
            'partner_id' => $partner->getKey(),
            'user_id' => $userId,
            'action' => $created ? PartnerRequest::CREATED : PartnerRequest::UPDATED,
            'status' => PartnerStatusEnum::PENDING,
        ]);
    }

    /**
     * Le partenaire a pris le statut `$status` au back-office : sa demande en validation reçoit cette réponse ; `null`
     * sans demande en validation, ou pour un retour en validation.
     */
    public function answer(Partner $partner, PartnerStatusEnum $status, int|string|null $actorId): ?PartnerRequest
    {
        if ($status === PartnerStatusEnum::PENDING) {
            return null;
        }

        $open = PartnerRequest::query()->where('partner_id', $partner->getKey())->open()->latest('id')->first();
        if ($open === null) {
            return null;
        }

        $open->update(['status' => $status, 'answered_by' => $actorId, 'answered_at' => now()]);

        return $open;
    }
}
