<?php

namespace Modules\Partner\Listeners;

use Modules\Partner\Events\PartnerSubmitted;
use Modules\Partner\Services\PartnerNotifierService;

/**
 * Un partenaire a été ajouté ou modifié : ses comptes, l'auteur compris, sont prévenus (décision
 * 2026-11-24-partenaires-validation-notifications).
 */
final class NotifyPartnerUsersOfSubmission
{
    public function __construct(
        private readonly PartnerNotifierService $partnerNotifierService,
    ) {}

    public function handle(PartnerSubmitted $event): void
    {
        $this->partnerNotifierService->submitted($event->partner, $event->actorId, $event->created);
    }
}
