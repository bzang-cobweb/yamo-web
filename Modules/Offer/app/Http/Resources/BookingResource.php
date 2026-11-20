<?php

namespace Modules\Offer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Offer\Models\Booking;

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
     * @return array{id: string, offer_id: string, type: string, status: string, check_in: string, check_out: string|null, dates: list<string>, guests: int|null, guests_label: string|null, total: string|null}
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
            /** Les dates, une par ligne, rédigées dans la langue de la requête (« Arrivée le 5 déc. 2026 à partir de 14:00 », « Départ le 7 déc. 2026 avant 11:00 » ; « Visite le 28 nov. 2026 »). */
            'dates' => $booking->dateLines(app()->getLocale()),
            'guests' => $booking->guests,
            /** Le nombre de personnes, rédigé (« 2 personnes ») ; `null` sans voyageurs (une visite). */
            'guests_label' => $booking->guests === null ? null : trans_choice('offer::booking.notify.people', $booking->guests, ['count' => $booking->guests]),
            /** Le total figé (« 255 000 FCFA »), ou `null` (une visite). */
            'total' => $booking->formattedTotal(app()->getLocale()),
        ];
    }
}
