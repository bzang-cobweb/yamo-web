<?php

namespace Modules\Offer\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * La règle des demandes (décision 2026-11-19-contrat-reservations) : le membre
 * demande (le prix est figé à l'envoi), le propriétaire accepte ou refuse, le
 * membre annule ; chaque étape prévient l'autre partie.
 */
class BookingService
{
    public function __construct(private readonly BookingNotifier $notifier) {}

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
        $checkOut = $type === BookingTypeEnum::STAY ? Carbon::parse((string) $data['check_out'])->startOfDay() : null;
        $days = $checkOut === null ? null : (int) $checkIn->diffInDays($checkOut);
        $message = trim((string) ($data['message'] ?? ''));
        $unitPrice = $type === BookingTypeEnum::STAY ? $offer->price : null;
        $guests = $type === BookingTypeEnum::STAY ? (int) ($data['guests'] ?? 1) : null;

        return DB::transaction(function () use ($user, $offer, $type, $checkIn, $checkOut, $days, $guests, $message, $unitPrice): Booking {
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
            $this->notifier->notify($booking, BookingNotifier::REQUESTED);

            return $booking;
        });
    }

    /**
     * La réponse du propriétaire ; un refus peut porter un message.
     */
    public function answer(Booking $booking, bool $accept, ?string $refusalMessage = null): Booking
    {
        return DB::transaction(function () use ($booking, $accept, $refusalMessage): Booking {
            $message = trim((string) $refusalMessage);
            $booking->fill([
                'status' => $accept ? BookingStatusEnum::ACCEPTED : BookingStatusEnum::REFUSED,
                'refusal_message' => $accept || $message === '' ? null : $message,
                'answered_at' => now(),
            ])->save();
            $this->notifier->notify($booking, $accept ? BookingNotifier::ACCEPTED : BookingNotifier::REFUSED);

            return $booking;
        });
    }

    /**
     * Le membre annule sa demande ; le propriétaire est prévenu.
     */
    public function cancel(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $booking->fill(['status' => BookingStatusEnum::CANCELLED, 'cancelled_at' => now()])->save();
            $this->notifier->notify($booking, BookingNotifier::CANCELLED);

            return $booking;
        });
    }
}
