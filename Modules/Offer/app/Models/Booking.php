<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Modules\Auth\Models\User;
use Modules\Offer\Database\Factories\BookingFactory;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;

/**
 * Une demande d'un membre sur une offre (décision 2026-11-19-contrat-reservations) :
 * un séjour (arrivée, départ, voyageurs) ou une visite (date souhaitée), avec un
 * message ; le propriétaire l'accepte ou la refuse, le membre peut l'annuler.
 *
 * @property int $offer_id
 * @property int $user_id
 * @property BookingTypeEnum $type
 * @property Carbon $check_in
 * @property Carbon|null $check_out
 * @property int|null $guests
 * @property string|null $message
 * @property string|null $unit_price
 * @property string|null $total
 * @property string|null $currency
 * @property BookingStatusEnum $status
 * @property string|null $refusal_message
 * @property Carbon|null $answered_at
 * @property Carbon|null $cancelled_at
 * @property-read Offer $offer
 * @property-read User $user
 */
#[Fillable(['offer_id', 'user_id', 'type', 'check_in', 'check_out', 'guests', 'message', 'unit_price', 'total', 'currency', 'status', 'refusal_message', 'answered_at', 'cancelled_at'])]
#[UseFactory(BookingFactory::class)]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    public const MAX_DAYS = 365;

    public const MAX_GUESTS = 20;

    public const MESSAGE_MAX_LENGTH = 1000;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BookingTypeEnum::class,
            'status' => BookingStatusEnum::class,
            'check_in' => 'date',
            'check_out' => 'date',
            'guests' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'answered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * L'offre, même retirée depuis.
     *
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class)->withTrashed();
    }

    /**
     * Le membre qui demande.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le nombre de jours d'un séjour (départ moins arrivée) ; `null` pour une visite.
     */
    public function days(): ?int
    {
        return $this->check_out === null ? null : (int) $this->check_in->diffInDays($this->check_out);
    }

    /**
     * Le membre peut l'annuler : en attente, ou acceptée et pas encore commencée.
     */
    public function isCancellable(): bool
    {
        return $this->status === BookingStatusEnum::PENDING
            || ($this->status === BookingStatusEnum::ACCEPTED && $this->check_in->isAfter(today()));
    }

    /**
     * Le propriétaire peut y répondre : en attente seulement.
     */
    public function isAwaitingAnswer(): bool
    {
        return $this->status === BookingStatusEnum::PENDING;
    }

    /**
     * « du 3 déc. 2026 au 6 déc. 2026 », « le 3 déc. 2026 » (visite), dans la langue `$locale`.
     */
    public function period(string $locale): string
    {
        $in = $this->check_in->copy()->locale($locale)->isoFormat('ll');

        return $this->check_out === null
            ? __('offer::booking.period.visit', ['date' => $in], $locale)
            : __('offer::booking.period.stay', ['from' => $in, 'to' => $this->check_out->copy()->locale($locale)->isoFormat('ll')], $locale);
    }

    /**
     * Le total figé (« 135 000 FCFA »), ou `null`.
     */
    public function formattedTotal(string $locale): ?string
    {
        return $this->total === null ? null : Number::currency((float) $this->total, (string) $this->currency, $locale);
    }
}
