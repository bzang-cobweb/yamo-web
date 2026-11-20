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
            // Une visite : charges, caution et avance en mois, puis le total ; un séjour : la caution,
            // puis le total, toujours, même à 0 (décision 2026-11-20-caution-dans-le-total).
            ...($booking->type === BookingTypeEnum::VISIT ? $this->monthly($booking, $locale) : $this->stay($booking, $locale)),
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
            // Une visite (non meublé) : « Loyer par mois (sans charges) » (décision 2026-11-20-loyer-sans-charges).
            'label' => match (true) {
                $unit === null => __('offer::booking.lines.price'),
                $booking->type === BookingTypeEnum::VISIT => __('offer::booking.lines.rent_per', ['unit' => $unit]),
                default => __('offer::booking.lines.price_per', ['unit' => $unit]),
            },
            'value' => Number::currency((float) $offer->price, (string) $offer->currency, $locale),
        ];
    }

    /**
     * Une visite (non meublé), toujours, même à 0 : les charges du mois, la caution et l'avance
     * de loyer, le nombre de mois dans le libellé (« 2 mois de caution ») et leur montant, le
     * loyer fois ces mois, en valeur ; puis le total, leur somme (décision 2026-11-20-caution-en-montant).
     *
     * @return list<array{key: string, label: string, value: string}>
     */
    private function monthly(Booking $booking, string $locale): array
    {
        $offer = $booking->offer;
        if ($offer === null || $offer->price === null) {
            return [];
        }

        $unit = app(DomainRegistry::class)->require()->priceUnit($offer);
        $number = fn (string $key): float => is_numeric($value = $offer->detail($key)) ? (float) $value : 0.0;
        $money = fn (float $amount): string => Number::currency($amount, (string) $offer->currency, $locale);
        $rent = (float) $offer->price;
        $months = fn (string $key, float $count): array => [
            'key' => $key,
            'label' => trans_choice('offer::booking.lines.'.$key.'_months', (int) $count, ['count' => (int) $count]),
            'value' => $money($rent * $count),
        ];
        [$deposit, $advance] = [$number('deposit_months'), $number('advance_months')];

        return [
            ['key' => 'charges', 'label' => $unit === null ? __('offer::booking.lines.charges') : __('offer::booking.lines.charges_per', ['unit' => $unit]), 'value' => $money($number('charges'))],
            $months('deposit', $deposit),
            $months('advance', $advance),
            ['key' => 'total', 'label' => __('offer::booking.lines.total'), 'value' => $money($rent * ($deposit + $advance))],
        ];
    }

    /**
     * Un séjour (meublé), toujours, même à 0 : la caution, un montant, puis le total, le total
     * figé de la demande plus la caution (décision 2026-11-20-caution-dans-le-total).
     *
     * @return list<array{key: string, label: string, value: string}>
     */
    private function stay(Booking $booking, string $locale): array
    {
        $deposit = is_numeric($value = $booking->offer?->detail('deposit')) ? (float) $value : 0.0;
        $money = fn (float $amount): string => Number::currency($amount, (string) $booking->currency, $locale);

        return [
            ['key' => 'deposit', 'label' => __('offer::booking.lines.deposit'), 'value' => $money($deposit)],
            ...($booking->total === null ? [] : [['key' => 'total', 'label' => __('offer::booking.lines.total'), 'value' => $money((float) $booking->total + $deposit)]]),
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
