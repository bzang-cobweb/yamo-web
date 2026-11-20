<?php

namespace Modules\Offer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Offer\Models\Booking;

/**
 * Une demande, en léger, telle que la page d'une notification de son fil la reçoit
 * (décision 2026-11-20-notification-generique) : son type, son statut, ses dates, ses
 * voyageurs et son total dans la langue de la requête.
 *
 * @property Booking $resource
 */
class BookingResource extends JsonResource
{
    /**
     * @return array{id: string, offer_id: string, type: string, status: string, check_in: string, check_out: string|null, guests: int|null, total: string|null}
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
            /** Le total figé (« 255 000 FCFA »), ou `null` (une visite). */
            'total' => $booking->formattedTotal(app()->getLocale()),
        ];
    }
}
