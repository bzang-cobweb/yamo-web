<?php

namespace Modules\Offer\Lists\Concerns;

use App\Support\Currency;
use Illuminate\Database\Eloquent\Builder;
use Modules\Form\Support\Field;
use Modules\Location\Models\Address;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Lists\OfferRegions;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferAddress;

/**
 * Les filtres d'une liste d'offres : la région, la ville, le prix, puis ceux du domaine
 * (décision 2026-11-19-contrat-filtres-vitrine). Une seule définition pour
 * l'accueil et « Mes logements » (décision 2026-11-19-contrat-reservations-logements-profil).
 */
trait FiltersOffers
{
    /**
     * La ville (parmi les offres de `$offers`), le prix, les filtres du domaine, puis l'éditeur identifié.
     *
     * @param  Builder<Offer>  $offers  les offres dont les villes sont proposées
     * @param  bool  $regionRequired  la région obligatoire, la première par défaut (la recherche)
     * @param  bool  $withIdentified  le filtre « Membre identifié » ; pas pour ses propres offres
     * @return list<Field>
     */
    protected function offerFilters(Builder $offers, bool $regionRequired = false, bool $withIdentified = true): array
    {
        $regions = OfferRegions::options($offers);
        $domain = app(DomainRegistry::class)->require();
        $currency = (string) Currency::symbol(CurrentCountry::require()->currency);

        return array_values(array_filter([
            // La région avant la ville (décision 2026-11-25-region-de-l-adresse).
            // Région et ville en puces, plusieurs à la fois (décision 2026-11-26-region-ville-en-puces) ; sauf la région
            // de la recherche, une seule, choisie dans la carte en tête de la page.
            ! $domain->hasAddress() ? null : ($regionRequired ? Field::select('region')->withoutAllOption() : Field::chips('region'))
                // Son nom dans le pays courant (décision 2026-11-25-regions-par-pays).
                ->label(CurrentCountry::require()->regionLabel())
                ->options($regions)
                // Obligatoire dans la recherche : par défaut, la première région qui a des offres, dans l'ordre du
                // back-office (décision 2026-11-25-region-obligatoire-recherche).
                ->required($regionRequired)
                // Une chaîne, comme les valeurs des options (PHP fait des clés numériques des entiers).
                ->default($regionRequired && $regions !== [] ? (string) array_key_first($regions) : null)
                ->countryScoped(),
            ! $domain->hasAddress() ? null : Field::chips('city')
                ->label(__('offer::offer.filters.city'))
                // Les villes des régions choisies (décision 2026-11-25-ville-selon-region).
                ->dependsOn('region')
                // Visible dès qu'une région est choisie (décision 2026-11-26-region-ville-en-puces).
                ->visibleWhen('region', array_map('strval', array_keys($regions)))
                ->options($this->offerCities($offers))
                ->countryScoped(),
            // Un seul intervalle, min et max (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
            ! $domain->hasPrice() ? null : Field::range('price')
                // « Prix » quel que soit le domaine (décision 2026-11-26-filtre-prix-membre-identifie).
                ->label(__('offer::offer.filters.price'))
                ->suffix($currency)
                ->rules(['min:0']),
            ...$domain->filters(),
            // L'éditeur de l'offre identifié ou non (décision 2026-11-26-filtre-prix-membre-identifie).
            ! $withIdentified ? null : Field::segmented('identified')
                ->label(__('offer::offer.filters.identified'))
                ->options(['any' => __('offer::offer.filters.any'), 'yes' => __('offer::offer.filters.yes'), 'no' => __('offer::offer.filters.no')])
                ->default('any'),
        ]));
    }

    /**
     * Les villes des offres de `$offers`, par ordre alphabétique, chacune avec sa région pour parent (décision
     * 2026-11-25-ville-selon-region).
     *
     * @param  Builder<Offer>  $offers
     * @return array<string, array{label: string, parent: string}>
     */
    private function offerCities(Builder $offers): array
    {
        return OfferRegions::cities(Address::query()->whereIn('id', OfferAddress::query()->select('address_id')->whereIn('offer_id', $offers->select('offers.id'))));
    }

    /**
     * Restreint `$query` à la ville, au prix et aux filtres du domaine.
     *
     * @param  Builder<Offer>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyOfferFilters(Builder $query, array $filters): void
    {
        if (($filters['region'] ?? []) !== [] && ($filters['region'] ?? '') !== '') {
            OfferRegions::apply($query, $filters['region']);
        }
        // Une ville ou plusieurs (puces, décision 2026-11-26-region-ville-en-puces).
        $cities = array_values(array_filter(array_map('strval', (array) ($filters['city'] ?? [])), fn (string $city): bool => $city !== ''));
        if ($cities !== []) {
            $query->whereHas('address', fn (Builder $address) => $address->whereIn('city', $cities));
        }
        [$min, $max] = array_values((array) ($filters['price'] ?? [])) + [null, null];
        if (is_numeric($min)) {
            $query->where('price', '>=', $min);
        }
        if (is_numeric($max)) {
            $query->where('price', '<=', $max);
        }

        // « Oui » : l'éditeur identifié ; « Non » : pas identifié ; « Tous », ou une autre valeur, ne filtre rien.
        $identified = $filters['identified'] ?? 'any';
        if ($identified === 'yes') {
            $query->whereHas('user', fn (Builder $user) => $user->whereNotNull('identified_at'));
        } elseif ($identified === 'no') {
            $query->whereHas('user', fn (Builder $user) => $user->whereNull('identified_at'));
        }

        app(DomainRegistry::class)->require()->applyFilters($query, $filters);
    }
}
