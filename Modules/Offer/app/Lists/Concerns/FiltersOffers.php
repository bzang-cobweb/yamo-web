<?php

namespace Modules\Offer\Lists\Concerns;

use App\Support\Currency;
use Illuminate\Database\Eloquent\Builder;
use Modules\Form\Support\Field;
use Modules\Location\Models\Address;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferAddress;

/**
 * Les filtres d'une liste d'offres : la ville, le prix, puis ceux du domaine
 * (décision 2026-11-19-contrat-filtres-vitrine). Une seule définition pour
 * l'accueil et « Mes logements » (décision 2026-11-19-contrat-reservations-logements-profil).
 */
trait FiltersOffers
{
    /**
     * La ville (parmi les offres de `$offers`), le prix, puis les filtres du domaine.
     *
     * @param  Builder<Offer>  $offers  les offres dont les villes sont proposées
     * @return list<Field>
     */
    protected function offerFilters(Builder $offers): array
    {
        $domain = app(DomainRegistry::class)->require();
        $currency = (string) Currency::symbol(CurrentCountry::require()->currency);

        return array_values(array_filter([
            ! $domain->hasAddress() ? null : Field::select('city')
                ->label(__('offer::offer.filters.city'))
                ->options($this->offerCities($offers)),
            // Un seul intervalle, min et max (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
            ! $domain->hasPrice() ? null : Field::range('price')
                ->label($domain->priceLabel())
                ->suffix($currency)
                ->rules(['min:0']),
            ...$domain->filters(),
        ]));
    }

    /**
     * Les villes des offres de `$offers`, par ordre alphabétique.
     *
     * @param  Builder<Offer>  $offers
     * @return array<string, string>
     */
    private function offerCities(Builder $offers): array
    {
        return Address::query()
            ->whereIn('id', OfferAddress::query()->select('address_id')->whereIn('offer_id', $offers->select('offers.id')))
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city', 'city')
            ->all();
    }

    /**
     * Restreint `$query` à la ville, au prix et aux filtres du domaine.
     *
     * @param  Builder<Offer>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyOfferFilters(Builder $query, array $filters): void
    {
        if (isset($filters['city'])) {
            $query->whereHas('address', fn (Builder $address) => $address->where('city', $filters['city']));
        }
        [$min, $max] = array_values((array) ($filters['price'] ?? [])) + [null, null];
        if (is_numeric($min)) {
            $query->where('price', '>=', $min);
        }
        if (is_numeric($max)) {
            $query->where('price', '<=', $max);
        }

        app(DomainRegistry::class)->require()->applyFilters($query, $filters);
    }
}
