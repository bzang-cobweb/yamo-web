<?php

namespace Modules\Offer\Http\Controllers\Api;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\OfferCalendar;

/**
 * Le calendrier d'une offre, pour son propriétaire (décision
 * 2026-11-19-contrat-calendrier-offre) : un mois, le panneau d'un jour, bloquer et
 * débloquer un jour. Un autre compte reçoit 404 (policy `update`).
 */
class OfferCalendarController
{
    public function __construct(private readonly OfferCalendar $calendar) {}

    /**
     * Un mois (`month=AAAA-MM`, le mois courant par défaut) : l'état de chaque jour.
     */
    public function month(Request $request, Offer $offer): JsonResponse
    {
        /** @var array{month?: string|null} $data */
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = isset($data['month']) ? Carbon::createFromFormat('!Y-m', $data['month']) : today();

        return response()->json(['data' => $this->calendar->month($offer, $month, $this->locale($request))]);
    }

    /**
     * Le panneau d'un jour : ses demandes et l'action « Bloquer » ou « Débloquer ».
     */
    public function day(Request $request, Offer $offer, string $date): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $this->calendar->day($offer, $this->date($date), $user, $this->locale($request))]);
    }

    /**
     * Bloquer un jour à venir que n'occupe aucun séjour accepté.
     */
    public function block(Offer $offer, string $date): Response
    {
        $day = $this->date($date);
        if ($day->lessThan(today())) {
            throw ValidationException::withMessages(['date' => __('offer::booking.calendar.block_past')]);
        }
        $occupied = $offer->bookings()
            ->where('status', BookingStatusEnum::ACCEPTED)
            ->overlapping($day, $day->copy()->addDay())
            ->exists();
        if ($occupied) {
            throw ValidationException::withMessages(['date' => __('offer::booking.calendar.block_accepted')]);
        }

        // Déjà bloqué : rien à faire (deux envois simultanés : l'index unique garde un seul jour).
        if (! $offer->blockedDates()->whereDate('date', $day->toDateString())->exists()) {
            try {
                $offer->blockedDates()->create(['date' => $day->toDateString()]);
            } catch (UniqueConstraintViolationException) {
            }
        }

        return response()->noContent();
    }

    /**
     * Débloquer un jour.
     */
    public function unblock(Offer $offer, string $date): Response
    {
        $offer->blockedDates()->whereDate('date', $this->date($date)->toDateString())->delete();

        return response()->noContent();
    }

    private function date(string $date): Carbon
    {
        return Carbon::createFromFormat('!Y-m-d', $date) ?: abort(404);
    }

    private function locale(Request $request): string
    {
        $user = $request->user();

        return $user instanceof User ? $user->preferredLocale() : app()->getLocale();
    }
}
