<?php

namespace Modules\Partner\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Partner\Models\Partner;

/**
 * Un partenaire a été ajouté (`$created`) ou modifié au back-office par `$actorId`, sans changement de statut
 * (décision 2026-11-24-partenaire-demandes).
 */
class PartnerEdited
{
    use Dispatchable;

    public function __construct(
        public readonly Partner $partner,
        public readonly int|string|null $actorId,
        public readonly bool $created,
    ) {}
}
