<?php

namespace Modules\Partner\Listeners;

use Modules\Partner\Events\PartnerEdited;
use Modules\Partner\Services\PartnerNotifierService;

/**
 * Un partenaire a été ajouté ou modifié au back-office : ses administrateurs sont prévenus, pas celui qui l'a fait
 * (décision 2026-11-24-partenaire-demandes).
 */
final class NotifyPartnerAdminsOfEdit
{
    public function __construct(
        private readonly PartnerNotifierService $partnerNotifierService,
    ) {}

    public function handle(PartnerEdited $event): void
    {
        $this->partnerNotifierService->edited($event->partner, $event->actorId, $event->created);
    }
}
