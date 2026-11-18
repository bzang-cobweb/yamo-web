<?php

namespace Modules\Offer\Filament\Resources\OfferCategoryResource\Pages;

use App\Filament\Pages\Concerns\HasRecordTitleBreadcrumbs;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Offer\Filament\Resources\OfferCategoryResource;

class EditOfferCategory extends EditRecord
{
    use HasRecordTitleBreadcrumbs;

    protected static string $resource = OfferCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            OfferCategoryResource::deleteAction(DeleteAction::make()),
        ];
    }

    public function getRecordTitle(): string
    {
        // Le chemin : « Location › Meublée ».
        return OfferCategoryResource::getRecordTitle($this->getRecord()) ?? '';
    }
}
