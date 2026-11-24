<?php

namespace Modules\Partner\Listeners;

use Modules\Partner\Events\PartnerStatusChanged;
use Modules\Partner\Services\PartnerNotifierService;
use Modules\Partner\Services\PartnerRequestService;

/**
 * Le statut d'un partenaire a changé : sa demande en validation reçoit cette réponse, ses administrateurs sont
 * prévenus (décisions 2026-11-24-partenaires-notifications et 2026-11-24-partenaire-demandes).
 */
final class NotifyPartnerUsersOfStatus
{
    public function __construct(
        private readonly PartnerNotifierService $partnerNotifierService,
        private readonly PartnerRequestService $partnerRequestService,
    ) {}

    public function handle(PartnerStatusChanged $event): void
    {
        $request = $this->partnerRequestService->answer($event->partner, $event->partner->status, $event->actorId);
        $this->partnerNotifierService->statusChanged($event->partner, $event->actorId, $request);
    }
}
