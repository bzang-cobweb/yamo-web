<?php

namespace Modules\Partner\Filament\Resources\PartnerResource\Pages;

use App\Filament\Pages\Concerns\HasNoBreadcrumbs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Partner\Filament\Resources\PartnerResource;

class ListPartners extends ListRecords
{
    use HasNoBreadcrumbs;

    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
