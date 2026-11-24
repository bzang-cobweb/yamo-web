<?php

namespace Modules\Partner\Listeners;

use Modules\Partner\Events\PartnerStatusChanged;
use Modules\Partner\Services\PartnerNotifierService;

/**
 * Le statut d'un partenaire a changé : ses administrateurs sont prévenus (décisions
 * 2026-11-24-partenaires-notifications et 2026-11-24-partenaire-demandes).
 */
final class NotifyPartnerUsersOfStatus
{
    public function __construct(
        private readonly PartnerNotifierService $partnerNotifierService,
    ) {}

    public function handle(PartnerStatusChanged $event): void
    {
        $this->partnerNotifierService->statusChanged($event->partner, $event->actorId);
    }
}
