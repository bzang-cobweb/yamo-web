<?php

namespace Modules\Offer\Filament\Resources\OfferCategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Offer\Filament\Resources\OfferCategoryResource;
use Modules\Offer\Models\OfferCategory;

class CreateOfferCategory extends CreateRecord
{
    protected static string $resource = OfferCategoryResource::class;

    /**
     * À la fin de ses sœurs.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['position'] = (int) OfferCategory::query()->where('parent_id', $data['parent_id'] ?? null)->max('position') + 1;

        return $data;
    }
}
