<?php

namespace Modules\Location\Filament\Resources\CountryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Location\Filament\Resources\CountryResource;
use Modules\Location\Models\Country;

class EditCountry extends EditRecord
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumb(): string
    {
        return $this->record instanceof Country ? $this->record->label() : parent::getBreadcrumb();
    }
}
