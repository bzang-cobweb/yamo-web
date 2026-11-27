<?php

namespace Modules\Offer\Lists\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Location\Models\Address;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Lists\BookingFilters;
use Modules\Offer\Lists\OfferCardTitle;
use Modules\Offer\Lists\OfferRegions;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferAddress;

/**
 * Les filtres de « Mes réservations » (« Demandes » d'une offre a les siens, décision
 * 2026-11-21-demandes-d-une-offre) : statut réel, période d'après
 * les dates, ville du logement, le logement (avec une recherche), puis les types de l'offre servis par le
 * domaine. Statut, période, région, type et type de location en puces, plusieurs valeurs à la fois (décision
 * 2026-11-26-reservations-filtres-en-puces) ; ville et logement en liste déroulante (décisions 2026-11-21-reservations-filtre-statut,
 * 2026-11-21-reservations-periode-dates, 2026-11-21-reservations-filtre-type,
 * 2026-11-21-reservations-filtre-logement et 2026-11-21-filtre-logement-recherche).
 */
trait FiltersBookings
{
    /**
     * Les logements des demandes de la liste, supprimés compris, dans le pays courant.
     *
     * @return Builder<Offer>
     */
    abstract protected function bookedOffers(): Builder;

    /**
     * @return list<Field>
     */
    public function filters(): array
    {
        return [
            BookingFilters::statuses(),
            BookingFilters::period(chips: true),
            // Les types de l'offre, puis la région, la ville et le logement à la fin (décision
            // 2026-11-26-reservations-lieu-en-dernier).
            ...app(DomainRegistry::class)->require()->offerTypeFilters(),
            // La région avant la ville (décision 2026-11-25-region-de-l-adresse) ; la ville, celles des régions choisies.
            Field::chips('region')
                // Son nom dans le pays courant (décision 2026-11-25-regions-par-pays).
                ->label(CurrentCountry::require()->regionLabel())
                ->options($regions = OfferRegions::options($this->bookedOffers()))
                ->countryScoped(),
            // En puces, comme la région (décision 2026-11-26-region-ville-en-puces).
            Field::chips('city')
                ->label(__('offer::booking.profile.filter_city'))
                // Les villes de la région choisie (décision 2026-11-25-ville-selon-region).
                ->dependsOn('region')
                // Visible seulement une région choisie (décision 2026-11-26-ville-et-logement-apres-region).
                ->visibleWhen('region', $chosen = array_map('strval', array_keys($regions)))
                ->options($this->bookedCities())
                ->countryScoped(),
            Field::select('offer')
                ->label(__('offer::booking.profile.filter_offer'))
                ->options($this->bookedOfferOptions())
                ->searchable()
                // Comme la ville, seulement une région choisie (décision 2026-11-26-ville-et-logement-apres-region).
                ->visibleWhen('region', $chosen)
                // Les logements du pays courant (décision 2026-11-25-region-en-tete-recherche).
                ->countryScoped(),
        ];
    }

    /**
     * @param  Builder<Booking>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        BookingFilters::applyStatuses($query, $filters);
        BookingFilters::applyPeriod($query, $filters);
        if (($filters['region'] ?? []) !== [] && ($filters['region'] ?? '') !== '') {
            $query->whereHas('offer', fn (Builder $offer) => OfferRegions::apply($offer->withTrashed(), $filters['region']));
        }
        $cities = array_values(array_filter(array_map('strval', (array) ($filters['city'] ?? [])), fn (string $city): bool => $city !== ''));
        if ($cities !== []) {
            $query->whereHas('offer', fn (Builder $offer) => $offer->withTrashed()->whereHas('address', fn (Builder $address) => $address->whereIn('city', $cities)));
        }
        if (isset($filters['offer']) && is_scalar($filters['offer'])) {
            $query->where('offer_id', (string) $filters['offer']);
        }
        $names = array_map(fn (Field $field): string => $field->name, app(DomainRegistry::class)->require()->offerTypeFilters());
        $types = array_intersect_key($filters, array_flip($names));
        if ($types !== []) {
            $query->whereHas('offer', fn (Builder $offer) => app(DomainRegistry::class)->require()->applyFilters($offer->withTrashed(), $types));
        }
    }

    /**
     * Les logements, par leur titre de carte.
     *
     * @return array<string, string>
     */
    protected function bookedOfferOptions(): array
    {
        $locale = $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();

        return $this->bookedOffers()
            ->with('address')
            ->orderBy('offers.id')
            ->get()
            ->mapWithKeys(fn (Offer $offer): array => [(string) $offer->getKey() => OfferCardTitle::of($offer, $locale)[0]])
            ->all();
    }

    /**
     * Les villes des logements, chacune avec sa région pour parent (décision 2026-11-25-ville-selon-region).
     *
     * @return array<string, array{label: string, parent: string}>
     */
    private function bookedCities(): array
    {
        return OfferRegions::cities(Address::query()->whereIn('id', OfferAddress::query()->select('address_id')->whereIn('offer_id', $this->bookedOffers()->select('offers.id'))));
    }
}
