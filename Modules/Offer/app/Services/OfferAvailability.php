<?php

namespace Modules\Offer\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * La disponibilité d'une offre (décision 2026-11-19-contrat-calendrier-offre) : un
 * jour bloqué n'est ni demandable ni acceptable ; deux séjours acceptés ne se
 * chevauchent jamais. Un séjour occupe ses nuits, de l'arrivée à la veille du
 * départ : le jour du départ reste libre pour une arrivée. Les visites n'occupent
 * rien : seuls les jours bloqués les empêchent.
 */
class OfferAvailability
{
    /**
     * La demande `[$in, $out[` (séjour) ou le jour `$in` (visite) heurte un jour
     * bloqué ou un séjour accepté (autre que `$except`).
     */
    public function conflicts(Offer $offer, BookingTypeEnum $type, Carbon $in, ?Carbon $out, ?int $except = null): bool
    {
        $to = $type === BookingTypeEnum::STAY && $out !== null ? $out : $in->copy()->addDay();

        if ($offer->blockedDates()->whereDate('date', '>=', $in->toDateString())->whereDate('date', '<', $to->toDateString())->exists()) {
            return true;
        }

        return $type === BookingTypeEnum::STAY && $offer->bookings()
            ->where('status', BookingStatusEnum::ACCEPTED)
            ->overlapping($in, $to)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except))
            ->exists();
    }

    /**
     * Les séjours en attente qui chevauchent `$booking`, par date d'arrivée : ils
     * sont refusés quand `$booking` est acceptée.
     *
     * @return Collection<int, Booking>
     */
    public function overlappingPending(Booking $booking): Collection
    {
        if ($booking->type !== BookingTypeEnum::STAY || $booking->check_out === null) {
            return new Collection;
        }

        return Booking::query()
            ->where('offer_id', $booking->offer_id)
            ->where('status', BookingStatusEnum::PENDING)
            ->whereKeyNot($booking->getKey())
            ->overlapping($booking->check_in, $booking->check_out)
            ->with('user')
            ->orderBy('check_in')
            ->get();
    }

    /**
     * Les jours de `[$from, $to[` qu'une demande de type `$type` ne peut pas
     * prendre : bloqués, et, pour un séjour, les nuits des séjours acceptés.
     *
     * @return list<string> dates `AAAA-MM-JJ`, triées
     */
    public function unavailableDays(Offer $offer, BookingTypeEnum $type, Carbon $from, Carbon $to): array
    {
        $days = $offer->blockedDates()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<', $to->toDateString())
            ->pluck('date')
            ->map(fn (Carbon $date): string => $date->toDateString())
            ->all();

        if ($type === BookingTypeEnum::STAY) {
            foreach ($offer->bookings()->where('status', BookingStatusEnum::ACCEPTED)->overlapping($from, $to)->get() as $stay) {
                for ($day = $stay->check_in->copy()->max($from); $day->lessThan($stay->check_out) && $day->lessThan($to); $day->addDay()) {
                    $days[] = $day->toDateString();
                }
            }
        }

        $days = array_values(array_unique($days));
        sort($days);

        return $days;
    }
}
