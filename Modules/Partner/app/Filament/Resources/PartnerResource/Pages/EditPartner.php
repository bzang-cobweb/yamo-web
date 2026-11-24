<?php

namespace Modules\Partner\Filament\Resources\PartnerResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Events\PartnerEdited;
use Modules\Partner\Filament\Resources\PartnerResource;
use Modules\Partner\Models\Partner;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    /** Le statut avant l'enregistrement : un changement de statut est annoncé à part (`PartnerStatusChanged`). */
    private ?PartnerStatusEnum $statusBefore = null;

    protected function beforeSave(): void
    {
        $record = $this->getRecord();
        $this->statusBefore = $record instanceof Partner ? $record->getOriginal('status') : null;
    }

    /**
     * Modifié au back-office sans changement de statut : ses administrateurs sont prévenus, pas l'auteur (décision
     * 2026-11-24-partenaire-demandes).
     */
    protected function afterSave(): void
    {
        $record = $this->getRecord();
        if (! $record instanceof Partner || $record->status !== $this->statusBefore) {
            return;
        }

        PartnerEdited::dispatch($record, auth()->id(), false);
    }

    protected function getHeaderActions(): array
    {
        return [
            PartnerResource::validateAction(),
            DeleteAction::make(),
        ];
    }
}
