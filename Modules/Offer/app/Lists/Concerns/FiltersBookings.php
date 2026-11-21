<?php

namespace Modules\Offer\Lists\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Location\Models\Address;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Lists\BookingFilters;
use Modules\Offer\Lists\OfferCardTitle;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferAddress;

/**
 * Les filtres de « Mes réservations » (« Demandes » d'une offre a les siens, décision
 * 2026-11-21-demandes-d-une-offre) : statut réel, période d'après
 * les dates, ville du logement, le logement (avec une recherche), puis les types de l'offre servis par le
 * domaine ; tous en liste déroulante (décisions 2026-11-21-reservations-filtre-statut,
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
            Field::select('status')
                ->label(__('offer::booking.profile.filter_status'))
                ->options(BookingStatusEnum::options()),
            BookingFilters::period(),
            Field::select('city')
                ->label(__('offer::booking.profile.filter_city'))
                ->options($this->bookedCities()),
            Field::select('offer')
                ->label(__('offer::booking.profile.filter_offer'))
                ->options($this->bookedOfferOptions())
                ->searchable(),
            ...app(DomainRegistry::class)->require()->offerTypeFilters(),
        ];
    }

    /**
     * @param  Builder<Booking>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        $status = BookingStatusEnum::tryFrom(is_string($filters['status'] ?? null) ? $filters['status'] : '');
        if ($status !== null) {
            $query->where('status', $status);
        }
        BookingFilters::applyPeriod($query, $filters);
        if (isset($filters['city'])) {
            $query->whereHas('offer', fn (Builder $offer) => $offer->withTrashed()->whereHas('address', fn (Builder $address) => $address->where('city', $filters['city'])));
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
     * Les villes des logements.
     *
     * @return array<string, string>
     */
    private function bookedCities(): array
    {
        return Address::query()
            ->whereIn('id', OfferAddress::query()->select('address_id')->whereIn('offer_id', $this->bookedOffers()->select('offers.id')))
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city', 'city')
            ->all();
    }
}
