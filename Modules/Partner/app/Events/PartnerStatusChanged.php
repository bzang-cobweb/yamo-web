<?php

namespace Modules\Partner\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Models\Partner;

/**
 * Le statut d'un partenaire a changé (décision 2026-11-24-partenaires-notifications), au back-office comme depuis
 * l'app ; `$actorId` : le compte qui l'a changé, `null` sans compte (une commande).
 */
class PartnerStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Partner $partner,
        public readonly PartnerStatusEnum $previous,
        public readonly int|string|null $actorId = null,
    ) {}
}
