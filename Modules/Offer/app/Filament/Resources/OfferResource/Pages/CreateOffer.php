<?php

namespace Modules\Offer\Filament\Resources\OfferResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Filament\Resources\OfferResource;
use Modules\Offer\Services\OfferService;

class CreateOffer extends CreateRecord
{
    protected static string $resource = OfferResource::class;

    /**
     * La devise est celle du domaine, dès qu'un prix est saisi ; les attributs
     * sont typés comme dans l'API.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $domain = app(DomainRegistry::class)->require();
        $data['currency'] = filled($data['price'] ?? null) ? $domain->currency() : null;
        $data['details'] = app(OfferService::class)->typedDetails($domain, (array) ($data['details'] ?? []));

        return $data;
    }
}
