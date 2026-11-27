<?php

namespace Modules\Offer\Filament\Resources\OfferResource\Pages;

use Bza\Form\Support\Locales;
use Filament\Resources\Pages\CreateRecord;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Filament\Resources\OfferResource;
use Modules\Offer\Services\OfferService;

class CreateOffer extends CreateRecord
{
    protected static string $resource = OfferResource::class;

    /**
     * Les attributs sont typés comme dans l'API ; la devise est celle du pays de l'offre (décision
     * 2026-11-25-devise-du-pays).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $domain = app(DomainRegistry::class)->require();
        $data['details'] = app(OfferService::class)->typedDetails($domain, (array) ($data['details'] ?? []));
        // La langue d'origine : celle par défaut si son titre est saisi, sinon la première saisie
        // (décision 2026-11-19-contrat-langue-saisie-allemand).
        $titles = array_keys(array_filter((array) ($data['title'] ?? []), fn (mixed $title): bool => filled($title)));
        $data['locale'] = in_array(Locales::default(), $titles, true) ? Locales::default() : ($titles[0] ?? Locales::default());

        return $data;
    }
}
