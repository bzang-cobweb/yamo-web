<?php

namespace Modules\Offer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Enums\RentalTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * Une demande, en léger, telle que la page d'une notification de son fil la reçoit
 * (décision 2026-11-20-notification-generique) : son type, son statut, ses dates, son
 * offre (titre, type de location), et ses lignes à afficher, libellé et valeur, dans la
 * langue de la requête (décision 2026-11-20-lignes-demande).
 *
 * @property Booking $resource
 */
class BookingResource extends JsonResource
{
    /**
     * @return array{id: string, offer_id: string, type: string, status: string, check_in: string, check_out: string|null, guests: int|null, offer: array{title: string|null, rental_type: string|null}, lines: list<array{key: string, label: string, value: string}>}
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
            'guests' => $booking->guests,
            /** L'offre : son titre saisi et son type de location, rédigé (`null` s'ils manquent). */
            'offer' => $this->offer($booking->offer),
            /**
             * Les lignes à afficher, dans l'ordre, libellé et valeur rédigés : personnes,
             * arrivée, départ (ou visite, seconde date), durée, prix, total. Une
             * ligne sans valeur n'est pas servie.
             */
            'lines' => $this->lines($booking),
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: string}>
     */
    private function lines(Booking $booking): array
    {
        $locale = app()->getLocale();
        $days = $booking->days();
        $line = fn (string $key, mixed $value): ?array => $value === null || $value === '' ? null : ['key' => $key, 'label' => __('offer::booking.lines.'.$key), 'value' => (string) $value];

        $lines = [
            $line('guests', $booking->guests),
            ...array_map(fn (array $date): array => ['key' => $date['key'], 'label' => $date['label'], 'value' => $date['value']], $booking->dateLines($locale)),
            $line('days', $days === null ? null : trans_choice('offer::booking.notify.days', $days, ['count' => $days])),
            $this->price($booking, $locale),
            // Une visite : les charges du mois, puis le total du mois (prix + charges), toujours
            // (décision 2026-11-20-charges-visite) ; un séjour : son total figé.
            ...($booking->type === BookingTypeEnum::VISIT ? $this->monthly($booking, $locale) : [$line('total', $booking->formattedTotal($locale))]),
            // Après le total, la caution (et l'avance de loyer d'une visite), si le logement les
            // renseigne (décision 2026-11-20-caution-affichee).
            ...$this->deposit($booking, $locale),
        ];

        return array_values(array_filter($lines));
    }

    /**
     * Le prix du logement : l'unité dans le libellé (« Prix par jour », « Prix par mois »),
     * le montant seul en valeur (« 20 000 FCFA ») ; `null` sans prix.
     *
     * @return array{key: string, label: string, value: string}|null
     */
    private function price(Booking $booking, string $locale): ?array
    {
        $offer = $booking->offer;
        if ($offer === null || $offer->price === null) {
            return null;
        }

        $unit = app(DomainRegistry::class)->require()->priceUnit($offer);

        return [
            'key' => 'price',
            'label' => $unit === null ? __('offer::booking.lines.price') : __('offer::booking.lines.price_per', ['unit' => $unit]),
            'value' => Number::currency((float) $offer->price, (string) $offer->currency, $locale),
        ];
    }

    /**
     * Les charges par mois d'une visite, même à 0, puis le total par mois, prix et charges
     * additionnés, même égal au prix (décision 2026-11-20-charges-visite) ; rien sans prix.
     *
     * @return list<array{key: string, label: string, value: string}>
     */
    /**
     * La caution du logement : en mois et l'avance de loyer pour une visite (non meublé),
     * un montant pour un séjour (meublé) ; rien de ce qui n'est pas renseigné.
     *
     * @return list<array{key: string, label: string, value: string}>
     */
    private function deposit(Booking $booking, string $locale): array
    {
        $offer = $booking->offer;
        if ($offer === null) {
            return [];
        }

        $months = fn (string $key, mixed $count): ?array => is_numeric($count)
            ? ['key' => $key, 'label' => __('offer::booking.lines.'.$key), 'value' => trans_choice('offer::booking.lines.months', (int) $count, ['count' => (int) $count])]
            : null;

        $lines = $booking->type === BookingTypeEnum::VISIT
            ? [$months('deposit', $offer->detail('deposit_months')), $months('advance', $offer->detail('advance_months'))]
            : [is_numeric($amount = $offer->detail('deposit'))
                ? ['key' => 'deposit', 'label' => __('offer::booking.lines.deposit'), 'value' => Number::currency((float) $amount, (string) $offer->currency, $locale)]
                : null];

        return array_values(array_filter($lines));
    }

    private function monthly(Booking $booking, string $locale): array
    {
        $offer = $booking->offer;
        if ($offer === null || $offer->price === null) {
            return [];
        }

        $unit = app(DomainRegistry::class)->require()->priceUnit($offer);
        $charges = is_numeric($value = $offer->detail('charges')) ? (float) $value : 0.0;
        $money = fn (float $amount): string => Number::currency($amount, (string) $offer->currency, $locale);
        $label = fn (string $key): string => $unit === null ? __('offer::booking.lines.'.$key) : __('offer::booking.lines.'.$key.'_per', ['unit' => $unit]);

        return [
            ['key' => 'charges', 'label' => $label('charges'), 'value' => $money($charges)],
            ['key' => 'total', 'label' => $label('total'), 'value' => $money((float) $offer->price + $charges)],
        ];
    }

    /**
     * @return array{title: string|null, rental_type: string|null}
     */
    private function offer(?Offer $offer): array
    {
        return [
            // Le titre saisi par le propriétaire, dans la langue de la requête.
            'title' => $offer === null ? null : (string) $offer->getTranslation('title', app()->getLocale()),
            // Le type de location, rédigé (« Location meublée »), ou `null`.
            'rental_type' => $offer === null ? null : RentalTypeEnum::of($offer)?->label(),
        ];
    }
}
