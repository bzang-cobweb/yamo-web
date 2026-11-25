<?php

namespace Modules\Offer\Lists;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Location\Models\Address;
use Modules\Location\Models\Region;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferAddress;

/**
 * Les régions des offres (décision 2026-11-25-region-de-l-adresse) : le filtre « Région », placé avant la ville
 * partout où elle se filtre, et les carrousels de l'accueil. Une région n'est proposée que si une offre y est.
 */
class OfferRegions
{
    /**
     * Les régions des adresses des offres de `$offers`, dans l'ordre du back-office : id => nom.
     *
     * @param  Builder<Offer>  $offers
     * @return array<string, string>
     */
    public static function options(Builder $offers): array
    {
        return self::of($offers)
            ->mapWithKeys(fn (Region $region): array => [(string) $region->getKey() => self::name($region)])
            ->all();
    }

    /**
     * Les régions des adresses des offres de `$offers`, dans l'ordre du back-office.
     *
     * @param  Builder<Offer>  $offers
     * @return Collection<int, Region>
     */
    public static function of(Builder $offers): Collection
    {
        $addresses = Address::query()->select('region_id')->whereNotNull('region_id')
            ->whereIn('id', OfferAddress::query()->select('address_id')->whereIn('offer_id', $offers->select('offers.id')));

        return Region::query()->whereIn('id', $addresses)->orderBy('position')->orderBy('id')->get();
    }

    /**
     * Restreint `$offers` à celles dont l'adresse est dans la région `$region`.
     *
     * @param  Builder<Offer>  $offers
     */
    public static function apply(Builder $offers, mixed $region): void
    {
        $offers->whereHas('address', fn (Builder $address) => $address->where('region_id', (int) $region));
    }

    /** Son nom dans la langue de l'app, sans espaces en trop. */
    public static function name(Region $region): string
    {
        return trim((string) $region->getTranslation('name', app()->getLocale()));
    }
}
