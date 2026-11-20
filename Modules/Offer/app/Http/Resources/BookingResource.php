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
            // Une visite : charges, loyer (décision 2026-11-20-loyer-apres-charges), caution et avance,
            // puis le total ; un séjour : prix, caution, puis le total, toujours, même à 0
            // (décision 2026-11-20-caution-dans-le-total).
            ...($booking->type === BookingTypeEnum::VISIT
                ? $this->monthly($booking, $locale)
                : [$this->price($booking, $locale), ...$this->stay($booking, $locale)]),
        ];

        return array_values(array_filter($lines));
    }

    /**
     * Le prix du logement : l'unité dans le libellé (« Prix par jour » ; « Loyer mensuel » pour une visite),
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
            // Une visite (non meublé) : « Loyer mensuel » (décision 2026-11-20-libelles-mensuels).
            'label' => match (true) {
                $booking->type === BookingTypeEnum::VISIT => __('offer::booking.lines.rent'),
                $unit === null => __('offer::booking.lines.price'),
                default => __('offer::booking.lines.price_per', ['unit' => $unit]),
            },
            'value' => Number::currency((float) $offer->price, (string) $offer->currency, $locale),
        ];
    }

    /**
     * Une visite (non meublé) : les charges du mois, le loyer, la caution et l'avance de loyer —
     * charges, caution et avance seulement si renseignées et non nulles (décision
     * 2026-11-20-lignes-vides-masquees) — le nombre de mois dans le libellé (« 2 mois de caution ») et leur montant, le
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

        $number = fn (string $key): float => is_numeric($value = $offer->detail($key)) ? (float) $value : 0.0;
        $money = fn (float $amount): string => Number::currency($amount, (string) $offer->currency, $locale);
        $rent = (float) $offer->price;
        $months = fn (string $key, float $count): ?array => $count <= 0 ? null : [
            'key' => $key,
            'label' => trans_choice('offer::booking.lines.'.$key.'_months', (int) $count, ['count' => (int) $count]),
            'value' => $money($rent * $count),
        ];
        $charges = $number('charges');
        [$deposit, $advance] = [$number('deposit_months'), $number('advance_months')];

        return array_values(array_filter([
            // « Charges mensuelles » (décision 2026-11-20-loyer-mensuel).
            $charges <= 0 ? null : ['key' => 'charges', 'label' => __('offer::booking.lines.charges'), 'value' => $money($charges)],
            // Avec des charges : « Loyer mensuel (sans charges) » (décision 2026-11-20-loyer-sans-charges-avec-charges).
            $charges > 0 ? [...$this->price($booking, $locale), 'label' => __('offer::booking.lines.rent_net')] : $this->price($booking, $locale),
            $months('deposit', $deposit),
            $months('advance', $advance),
            // Avec des charges, le total le dit : « Total (sans charges) » (décision 2026-11-20-total-sans-charges).
            ['key' => 'total', 'label' => __($charges > 0 ? 'offer::booking.lines.total_without_charges' : 'offer::booking.lines.total'), 'value' => $money($rent * ($deposit + $advance))],
        ]));
    }

    /**
     * Un séjour (meublé) : la caution, un montant, si renseignée et non nulle (décision
     * 2026-11-20-lignes-vides-masquees), puis le total, le total
     * figé de la demande plus la caution (décision 2026-11-20-caution-dans-le-total).
     *
     * @return list<array{key: string, label: string, value: string}>
     */
    private function stay(Booking $booking, string $locale): array
    {
        $deposit = is_numeric($value = $booking->offer?->detail('deposit')) ? (float) $value : 0.0;
        $money = fn (float $amount): string => Number::currency($amount, (string) $booking->currency, $locale);

        return [
            ...($deposit <= 0 ? [] : [['key' => 'deposit', 'label' => __('offer::booking.lines.deposit'), 'value' => $money($deposit)]]),
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
