<?php

namespace Modules\Partner\Filament\Resources\PartnerResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Partner\Events\PartnerEdited;
use Modules\Partner\Filament\Resources\PartnerResource;
use Modules\Partner\Models\Partner;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;

    /** Ajouté au back-office : ses administrateurs sont prévenus, pas l'auteur (décision 2026-11-24-partenaire-demandes). */
    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        if ($record instanceof Partner) {
            PartnerEdited::dispatch($record, auth()->id(), true);
        }
    }
}
