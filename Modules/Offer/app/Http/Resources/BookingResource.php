<?php

namespace Modules\Offer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\RentalTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * Une demande, en léger, telle que la page d'une notification de son fil la reçoit
 * (décision 2026-11-20-notification-generique) : son type, son statut, ses dates (aussi
 * rédigées, une par ligne), ses voyageurs et son total dans la langue de la requête.
 *
 * @property Booking $resource
 */
class BookingResource extends JsonResource
{
    /**
     * @return array{id: string, offer_id: string, type: string, status: string, check_in: string, check_out: string|null, offer: array{title: string|null, rental_type: string|null, bedrooms: string|null}, dates: list<array{label: string, value: string}>, days_label: string|null, guests: int|null, guests_label: string|null, price_label: string|null, total: string|null}
     */
    public function toArray(Request $request): array
    {
        $booking = $this->resource;

        return [
            'id' => (string) $booking->getKey(),
            'offer_id' => (string) $booking->offer_id,
            /** `stay` (séjour) ou `visit` (visite). */
            'type' => $booking->type->value,
            /** `pending`, `accepted`, `refused`, `cancelled`. */
            'status' => $booking->status->value,
            'check_in' => $booking->check_in->toDateString(),
            /** Le départ d'un séjour, la seconde date proposée d'une visite, ou `null`. */
            'check_out' => $booking->check_out?->toDateString(),
            /** L'offre : son titre saisi, son type de location et son nombre de chambres, rédigés (`null` s'ils manquent). */
            'offer' => $this->offer($booking->offer),
            /** Les dates, une par ligne, libellé et valeur, rédigées dans la langue de la requête (« Arrivée » — « 5 déc. 2026 à partir de 14:00 » ; « Visite » — « 28 nov. 2026 »). */
            'dates' => $booking->dateLines(app()->getLocale()),
            /** La durée d'un séjour, rédigée (« 2 jours ») ; `null` pour une visite. */
            'days_label' => ($days = $booking->days()) === null ? null : trans_choice('offer::booking.notify.days', $days, ['count' => $days]),
            'guests' => $booking->guests,
            /** Le nombre de personnes, rédigé (« 2 personnes ») ; `null` sans voyageurs (une visite). */
            'guests_label' => $booking->guests === null ? null : trans_choice('offer::booking.notify.people', $booking->guests, ['count' => $booking->guests]),
            /** Le prix du logement, rédigé (« 20 000 FCFA / jour », « 400 000 FCFA / mois ») ; `null` sans prix. */
            'price_label' => app(DomainRegistry::class)->require()->formattedPrice($booking->offer, app()->getLocale()),
            /** Le total figé (« 255 000 FCFA »), ou `null` (une visite). */
            'total' => $booking->formattedTotal(app()->getLocale()),
        ];
    }

    /**
     * @return array{title: string|null, rental_type: string|null, bedrooms: string|null}
     */
    private function offer(?Offer $offer): array
    {
        return [
            // Le titre saisi par le propriétaire, dans la langue de la requête.
            'title' => $offer === null ? null : (string) $offer->getTranslation('title', app()->getLocale()),
            // Le type de location, rédigé (« Location meublée »), ou `null`.
            'rental_type' => $offer === null ? null : RentalTypeEnum::of($offer)?->label(),
            // Le nombre de chambres, rédigé (« 2 chambres », « Sans chambre »), ou `null` s'il n'est pas renseigné.
            'bedrooms' => is_numeric($bedrooms = $offer?->detail('bedrooms')) ? trans_choice('offer::booking.notify.bedrooms', (int) $bedrooms, ['count' => (int) $bedrooms]) : null,
        ];
    }
}
