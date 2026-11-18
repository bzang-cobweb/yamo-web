<?php

namespace Modules\Offer\Filament\Resources\OfferResource\Pages;

use App\Filament\Pages\Concerns\HasNoBreadcrumbs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Offer\Filament\Resources\OfferResource;

class ListOffers extends ListRecords
{
    use HasNoBreadcrumbs;

    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
