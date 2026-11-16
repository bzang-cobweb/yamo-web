<?php

namespace Modules\Location\Filament\Resources\CountryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Modules\Location\Filament\Resources\CountryResource;
use Modules\Location\Models\Country;

class EditCountry extends EditRecord
{
    use Translatable;

    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumb(): string
    {
        return $this->record instanceof Country ? $this->record->label() : parent::getBreadcrumb();
    }
}
