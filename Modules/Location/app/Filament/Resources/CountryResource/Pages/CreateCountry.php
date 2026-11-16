<?php

namespace Modules\Location\Filament\Resources\CountryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Modules\Location\Filament\Resources\CountryResource;

class CreateCountry extends CreateRecord
{
    use Translatable;

    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
