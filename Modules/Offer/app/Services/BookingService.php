<?php

namespace Modules\Offer\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Events\BookingAnswered;
use Modules\Offer\Events\BookingCancelled;
use Modules\Offer\Events\BookingRequested;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * La règle des demandes (décision 2026-11-19-contrat-reservations) : le membre
 * demande (le prix est figé à l'envoi), le propriétaire accepte ou refuse, le
 * membre annule. Chaque étape est un événement (`BookingRequested`, `BookingAnswered`,
 * `BookingCancelled`) ; les notifications en découlent (décision
 * 2026-11-19-notifications-reservations).
 */
class BookingService
{
    public function __construct(private readonly OfferAvailability $availability) {}

    /**
     * Une demande validée par `OfferBookingForm` : séjour (arrivée, départ,
     * voyageurs) ou visite (date), selon le domaine.
     *
     * @param  array<string, mixed>  $data
     */
    public function request(User $user, Offer $offer, array $data): Booking
    {
        $type = app(DomainRegistry::class)->require()->bookingType($offer) ?? BookingTypeEnum::VISIT;
        $checkIn = Carbon::parse((string) $data['check_in'])->startOfDay();
        // Un séjour : le départ ; une visite : une seconde date proposée, facultative.
        $checkOut = filled($data['check_out'] ?? null) ? Carbon::parse((string) $data['check_out'])->startOfDay() : null;
        $days = $type === BookingTypeEnum::STAY && $checkOut !== null ? (int) $checkIn->diffInDays($checkOut) : null;
        $message = trim((string) ($data['message'] ?? ''));
        $unitPrice = $type === BookingTypeEnum::STAY ? $offer->price : null;
        $guests = $type === BookingTypeEnum::STAY ? (int) ($data['guests'] ?? 1) : null;

        return DB::transaction(function () use ($user, $offer, $type, $checkIn, $checkOut, $days, $guests, $message, $unitPrice): Booking {
            // Un jour bloqué ou une nuit déjà prise : refusé (décision 2026-11-19-contrat-calendrier-offre).
            $this->lock($offer);
            if ($this->availability->conflicts($offer, $type, $checkIn, $checkOut)) {
                throw ValidationException::withMessages(['check_in' => __('offer::booking.unavailable')]);
            }

            $booking = $offer->bookings()->create([
                'user_id' => $user->getKey(),
                'type' => $type,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => $guests,
                'message' => $message === '' ? null : $message,
                'unit_price' => $unitPrice,
                'total' => $unitPrice === null || $days === null ? null : number_format((float) $unitPrice * $days, 2, '.', ''),
                'currency' => $unitPrice === null ? null : ($offer->currency ?? $offer->country?->currency),
                'status' => BookingStatusEnum::PENDING,
            ]);
            BookingRequested::dispatch($booking);

            return $booking;
        });
    }

    /**
     * La réponse du propriétaire ; un refus peut porter un message.
     */
    public function answer(Booking $booking, bool $accept, ?string $refusalMessage = null): Booking
    {
        return DB::transaction(function () use ($booking, $accept, $refusalMessage): Booking {
            if (! $accept) {
                return $this->settle($booking, false, $refusalMessage);
            }

            // Accepter : jamais sur un jour bloqué ni sur un séjour déjà accepté ; les
            // demandes en attente qui chevauchent sont refusées, leurs membres prévenus
            // (décision 2026-11-19-contrat-calendrier-offre).
            $this->lock($booking->offer);
            if ($this->availability->conflicts($booking->offer, $booking->type, $booking->check_in, $booking->check_out, $booking->getKey())) {
                throw ValidationException::withMessages(['answer' => __('offer::booking.accept_unavailable')]);
            }
            $this->settle($booking, true);
            foreach ($this->availability->overlappingPending($booking) as $other) {
                $this->settle($other, false, __('offer::booking.overlap_refusal', [], $other->user->preferredLocale()));
            }

            return $booking;
        });
    }

    /**
     * Bloquer un jour que n'occupe aucun séjour accepté, sous le même verrou que
     * l'acceptation : un blocage et une acceptation simultanés passent l'un après
     * l'autre (décision 2026-11-19-contrat-calendrier-offre). Déjà bloqué : rien à faire.
     */
    public function block(Offer $offer, Carbon $day): void
    {
        DB::transaction(function () use ($offer, $day): void {
            $this->lock($offer);
            $occupied = $offer->bookings()
                ->where('status', BookingStatusEnum::ACCEPTED)
                ->overlapping($day, $day->copy()->addDay())
                ->exists();
            if ($occupied) {
                throw ValidationException::withMessages(['date' => __('offer::booking.calendar.block_accepted')]);
            }
            if (! $offer->blockedDates()->whereDate('date', $day->toDateString())->exists()) {
                $offer->blockedDates()->create(['date' => $day->toDateString()]);
            }
        });
    }

    /**
     * Enregistre la réponse et la notifie ; un refus peut porter un message.
     */
    private function settle(Booking $booking, bool $accept, ?string $refusalMessage = null): Booking
    {
        $message = trim((string) $refusalMessage);
        $booking->fill([
            'status' => $accept ? BookingStatusEnum::ACCEPTED : BookingStatusEnum::REFUSED,
            'refusal_message' => $accept || $message === '' ? null : $message,
            'answered_at' => now(),
        ])->save();
        BookingAnswered::dispatch($booking);

        return $booking;
    }

    /**
     * Verrouille l'offre jusqu'à la fin de la transaction : deux demandes ou deux
     * acceptations simultanées sur la même offre passent l'une après l'autre.
     */
    private function lock(Offer $offer): void
    {
        Offer::query()->withTrashed()->whereKey($offer->getKey())->lockForUpdate()->first();
    }

    /**
     * Le membre annule sa demande ; le propriétaire est prévenu.
     */
    public function cancel(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $booking->fill(['status' => BookingStatusEnum::CANCELLED, 'cancelled_at' => now()])->save();
            BookingCancelled::dispatch($booking);

            return $booking;
        });
    }
}
