<?php

namespace Modules\Offer\Filament\Resources\OfferCategoryResource\Pages;

use App\Filament\Pages\Concerns\HasNoBreadcrumbs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Offer\Filament\Resources\OfferCategoryResource;

class ListOfferCategories extends ListRecords
{
    use HasNoBreadcrumbs;

    protected static string $resource = OfferCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
