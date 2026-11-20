<?php

namespace Modules\Offer\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
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
 * @property Carbon|null $check_out départ d'un séjour ; seconde date proposée d'une visite (facultative)
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

    /** Le préfixe du fil de notifications d'une demande : `booking:12` (décision 2026-11-20-notification-generique). */
    public const THREAD_PREFIX = 'booking:';

    /** Le fil de notifications de cette demande. */
    public function thread(): string
    {
        return self::THREAD_PREFIX.$this->getKey();
    }

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

    /** Les regroupements de statuts des filtres : à venir, en attente, passées. */
    public const STATUS_GROUPS = ['upcoming', 'pending', 'past'];

    /**
     * Restreint à un regroupement de statuts (décision
     * 2026-11-19-contrat-reservations-logements-profil) : `upcoming` (acceptées, pas
     * terminées), `pending` (en attente), `past` (refusées, annulées, ou terminées).
     *
     * @param  Builder<self>  $query
     */
    public function scopeInStatusGroup(Builder $query, string $group): void
    {
        $today = today()->toDateString();
        $end = 'coalesce(check_out, check_in)';

        match ($group) {
            'upcoming' => $query->where('status', BookingStatusEnum::ACCEPTED)->whereRaw("{$end} >= ?", [$today]),
            'pending' => $query->where('status', BookingStatusEnum::PENDING),
            'past' => $query->where(fn (Builder $past) => $past
                ->whereIn('status', [BookingStatusEnum::REFUSED, BookingStatusEnum::CANCELLED])
                ->orWhere(fn (Builder $done) => $done->where('status', BookingStatusEnum::ACCEPTED)->whereRaw("{$end} < ?", [$today]))),
            default => null,
        };
    }

    /**
     * Les séjours qui occupent au moins une nuit de `[$from, $to[` : arrivée avant
     * `$to` et départ après `$from` (le jour du départ est libre pour une arrivée).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverlapping(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->where('type', BookingTypeEnum::STAY)
            ->whereDate('check_in', '<', $to->toDateString())
            ->whereDate('check_out', '>', $from->toDateString());
    }

    /**
     * Le nombre de jours d'un séjour (départ moins arrivée) ; `null` pour une visite.
     */
    public function days(): ?int
    {
        // Une visite n'a pas de durée : son `check_out` est une seconde date proposée.
        return $this->type !== BookingTypeEnum::STAY || $this->check_out === null ? null : (int) $this->check_in->diffInDays($this->check_out);
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
     * Les dates de la demande, une par ligne, libellé et valeur, dans la langue `$locale`
     * (décision 2026-11-20-heures-arrivee-depart) :
     * - un séjour : « Arrivée » — « 5 déc. 2026 à partir de 14:00 », puis « Départ » —
     *   « 7 déc. 2026 au plus tard à 11:00 » ; sans heure renseignée, la date seule ;
     * - une visite : « Visite » — « 28 nov. 2026 », et « Seconde date » — « 30 nov. 2026 »
     *   s'il y en a une.
     *
     * @return list<array{key: string, label: string, value: string}>
     */
    public function dateLines(string $locale): array
    {
        $in = $this->check_in->copy()->locale($locale)->isoFormat('ll');
        $out = $this->check_out?->copy()->locale($locale)->isoFormat('ll');
        $line = fn (string $key, string $value): array => ['key' => $key, 'label' => __('offer::booking.dates.'.$key, [], $locale), 'value' => $value];

        if ($this->type === BookingTypeEnum::VISIT) {
            return $out === null ? [$line('visit', $in)] : [$line('visit', $in), $line('second', $out)];
        }

        $from = $this->offer?->detail('check_in_from');
        $until = $this->offer?->detail('check_out_until');
        $lines = [$line('arrival', is_string($from) && $from !== '' ? __('offer::booking.dates.from', ['date' => $in, 'time' => $from], $locale) : $in)];
        if ($out !== null) {
            $lines[] = $line('departure', is_string($until) && $until !== '' ? __('offer::booking.dates.until', ['date' => $out, 'time' => $until], $locale) : $out);
        }

        return $lines;
    }

    /**
     * « du 3 déc. 2026 au 6 déc. 2026 », « le 3 déc. 2026 » (visite), dans la langue `$locale`.
     */
    public function period(string $locale): string
    {
        $in = $this->check_in->copy()->locale($locale)->isoFormat('ll');

        $out = $this->check_out?->copy()->locale($locale)->isoFormat('ll');

        if ($this->type === BookingTypeEnum::VISIT) {
            // Une visite : sa date, et la seconde date proposée s'il y en a une (`check_out`).
            return $out === null
                ? __('offer::booking.period.visit', ['date' => $in], $locale)
                : __('offer::booking.period.visit_or', ['date' => $in, 'second' => $out], $locale);
        }

        return $out === null
            ? __('offer::booking.period.visit', ['date' => $in], $locale)
            : __('offer::booking.period.stay', ['from' => $in, 'to' => $out], $locale);
    }

    /**
     * Le total figé (« 135 000 FCFA »), ou `null`.
     */
    public function formattedTotal(string $locale): ?string
    {
        return $this->total === null ? null : Number::currency((float) $this->total, (string) $this->currency, $locale);
    }
}
