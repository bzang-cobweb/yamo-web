<?php

namespace Modules\Location\Filament\Resources\CountryResource\Pages;

use App\Filament\Pages\Concerns\HasRecordTitleBreadcrumbs;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Location\Filament\Resources\CountryResource;

class EditCountry extends EditRecord
{
    use HasRecordTitleBreadcrumbs;

    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
