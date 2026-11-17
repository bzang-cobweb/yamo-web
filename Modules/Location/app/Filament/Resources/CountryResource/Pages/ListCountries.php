<?php

namespace Modules\Location\Filament\Resources\CountryResource\Pages;

use App\Filament\Pages\Concerns\HasNoBreadcrumbs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Location\Filament\Resources\CountryResource;

class ListCountries extends ListRecords
{
    use HasNoBreadcrumbs;

    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
