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
 * @property string|null $cancel_message
 * @property bool $show_address l'adresse du logement visible du demandeur (décision 2026-11-21-afficher-adresse)
 * @property Carbon|null $answered_at
 * @property Carbon|null $cancelled_at
 * @property-read Offer $offer
 * @property-read User $user
 */
#[Fillable(['offer_id', 'user_id', 'type', 'check_in', 'check_out', 'guests', 'message', 'unit_price', 'total', 'currency', 'status', 'refusal_message', 'cancel_message', 'show_address', 'answered_at', 'cancelled_at'])]
#[UseFactory(BookingFactory::class)]
class Booking extends Model
{
    /** L'adresse affichée par défaut, comme la colonne (décision 2026-11-21-afficher-adresse). */
    protected $attributes = ['show_address' => true];

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
            'show_address' => 'boolean',
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

    /** Les périodes d'une demande, d'après ses dates seulement (décision 2026-11-21-reservations-periode-dates). */
    public const PERIODS = ['upcoming', 'ongoing', 'past'];

    /**
     * Restreint à une période, d'après les dates seulement, quel que soit le statut (décision
     * 2026-11-21-reservations-periode-dates) : `upcoming` (pas encore commencée), `ongoing`
     * (commencée, pas finie ; le jour du départ compris), `past` (finie). Un séjour va de
     * l'arrivée au départ ; une visite, de la première à la dernière de ses dates proposées.
     *
     * @param  Builder<self>  $query
     */
    public function scopeInPeriod(Builder $query, string $period): void
    {
        $today = today()->toDateString();
        // `date()` : une date enregistrée avec son heure (« 2026-11-21 00:00:00 ») se compare au jour.
        $start = 'date(case when check_out is not null and check_out < check_in then check_out else check_in end)';
        $end = 'date(case when check_out is not null and check_out > check_in then check_out else check_in end)';

        match ($period) {
            'upcoming' => $query->whereRaw("{$start} > ?", [$today]),
            'ongoing' => $query->whereRaw("{$start} <= ?", [$today])->whereRaw("{$end} >= ?", [$today]),
            'past' => $query->whereRaw("{$end} < ?", [$today]),
            default => null,
        };
    }

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
            // En attente et pas expirée ; une expirée est passée (décision 2026-11-22-statuts-terminee-expiree).
            'pending' => $query->awaitingAnswer(),
            'past' => $query->where(fn (Builder $past) => $past
                ->whereIn('status', [BookingStatusEnum::REFUSED, BookingStatusEnum::CANCELLED])
                ->orWhere(fn (Builder $done) => $done->where('status', BookingStatusEnum::ACCEPTED)->whereRaw("{$end} < ?", [$today]))
                ->orWhere(fn (Builder $expired) => $expired->expired())),
            default => null,
        };
    }

    /**
     * Le jour à partir duquel une demande en attente expire, en SQL : l'arrivée d'un séjour ; la dernière date
     * proposée d'une visite (décision 2026-11-22-statuts-terminee-expiree).
     */
    private const EXPIRES_AFTER = "date(case when type = 'visit' and check_out is not null and check_out > check_in then check_out else check_in end)";

    /**
     * Les demandes en attente dont la date est passée : plus de réponse possible (décision
     * 2026-11-22-statuts-terminee-expiree).
     *
     * @param  Builder<self>  $query
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('status', BookingStatusEnum::PENDING)->whereRaw(self::EXPIRES_AFTER.' < ?', [today()->toDateString()]);
    }

    /**
     * Les demandes en attente d'une réponse : en attente et pas expirées.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAwaitingAnswer(Builder $query): void
    {
        $query->where('status', BookingStatusEnum::PENDING)->whereRaw(self::EXPIRES_AFTER.' >= ?', [today()->toDateString()]);
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
     * Le membre peut l'annuler : en attente (pas expirée), ou acceptée et pas encore commencée.
     */
    public function isCancellable(): bool
    {
        return $this->isAwaitingAnswer()
            || ($this->status === BookingStatusEnum::ACCEPTED && $this->check_in->isAfter(today()));
    }

    /**
     * Le propriétaire peut y répondre, le membre la modifier : en attente et pas expirée.
     */
    public function isAwaitingAnswer(): bool
    {
        return $this->status === BookingStatusEnum::PENDING && ! $this->isExpired();
    }

    /**
     * Le dernier jour de la demande : le départ d'un séjour, la dernière date proposée d'une visite.
     */
    public function lastDay(): Carbon
    {
        return $this->check_out !== null && $this->check_out->greaterThan($this->check_in) ? $this->check_out : $this->check_in;
    }

    /**
     * En attente, et sa date est passée : l'arrivée d'un séjour, la dernière date proposée d'une visite
     * (décision 2026-11-22-statuts-terminee-expiree).
     */
    public function isExpired(): bool
    {
        $day = $this->type === BookingTypeEnum::VISIT ? $this->lastDay() : $this->check_in;

        return $this->status === BookingStatusEnum::PENDING && $day->copy()->startOfDay()->lessThan(today());
    }

    /**
     * Acceptée, et son dernier jour est passé (décision 2026-11-22-statuts-terminee-expiree).
     */
    public function isCompleted(): bool
    {
        return $this->status === BookingStatusEnum::ACCEPTED && $this->lastDay()->copy()->startOfDay()->lessThan(today());
    }

    /**
     * Le statut affiché : « Terminée » ou « Expirée » d'après les dates, sinon le statut enregistré ; pas
     * enregistré (décision 2026-11-22-statuts-terminee-expiree).
     *
     * @return array{label: string, tone: string}
     */
    public function statusTag(): array
    {
        $shown = match (true) {
            $this->isCompleted() => 'completed',
            $this->isExpired() => 'expired',
            default => null,
        };

        return $shown === null
            ? ['label' => $this->status->label(), 'tone' => $this->status->tone()]
            : ['label' => __('offer::booking.statuses.'.$shown), 'tone' => 'neutral'];
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
     * « du 3 déc. 2026 au 6 déc. 2026 », « le 3 déc. 2026 » (visite), dans la langue `$locale` : le mois en lettres,
     * jamais en chiffres, pour éviter de confondre jour et mois (décision 2026-11-22-dates-sans-chiffres).
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
