<?php

namespace Modules\Partner\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Partner\Models\Partner;

/**
 * Un partenaire a été ajouté (`$created`) ou modifié depuis l'app par un de ses comptes (`$actorId`) : il est en
 * validation (décision 2026-11-24-partenaires-validation-notifications).
 */
class PartnerSubmitted
{
    use Dispatchable;

    public function __construct(
        public readonly Partner $partner,
        public readonly int|string $actorId,
        public readonly bool $created,
        // Une modification : les champs changés, avant et après (décision 2026-11-26-partenaire-modifications).
        /** @var list<array{field: string, old: string, new: string}> */
        public readonly array $changes = [],
    ) {}
}
