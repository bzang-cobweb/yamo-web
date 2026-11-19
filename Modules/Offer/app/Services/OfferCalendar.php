<?php

namespace Modules\Offer\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Submission;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Lists\ReceivedBookingListing;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * Le calendrier d'une offre, vu par son propriétaire (décision
 * 2026-11-19-contrat-calendrier-offre), décrit pour l'app : un mois en grille
 * (l'état de chaque jour, un point pour les visites) et le panneau d'un jour (ses
 * demandes, en cartes comme dans « Demandes reçues », et « Bloquer » ou
 * « Débloquer »). L'app ne connaît ni les demandes ni les offres.
 *
 * Un séjour occupe ses nuits, de l'arrivée à la veille du départ ; une visite, son jour.
 */
class OfferCalendar
{
    /** Les mois qu'on peut revoir avant le mois courant. */
    public const PAST_MONTHS = 12;

    /**
     * @return array{title: string, month: string, label: string, previous: string|null, next: string|null, weekdays: list<string>, offset: int, days: list<array{date: string, day: int, state: string, dot: string|null}>, legend: list<array{kind: string, tone: string, label: string}>}
     */
    public function month(Offer $offer, Carbon $month, string $locale): array
    {
        $from = $month->copy()->startOfMonth();
        $to = $from->copy()->addMonth();
        $current = today()->startOfMonth();
        $bookings = $this->bookings($offer, $from, $to);
        $blocked = $this->blocked($offer, $from, $to);

        $days = [];
        for ($day = $from->copy(); $day->lessThan($to); $day->addDay()) {
            $date = $day->toDateString();
            $stays = $bookings->filter(fn (Booking $booking): bool => $booking->type === BookingTypeEnum::STAY && $this->coversNight($booking, $day));
            $visits = $bookings->filter(fn (Booking $booking): bool => $booking->type === BookingTypeEnum::VISIT && $booking->check_in->isSameDay($day));
            $accepted = $stays->contains('status', BookingStatusEnum::ACCEPTED);
            $pending = $stays->contains('status', BookingStatusEnum::PENDING);
            $isBlocked = in_array($date, $blocked, true);

            $days[] = [
                'date' => $date,
                'day' => $day->day,
                /** `past`, `conflict`, `accepted`, `blocked`, `pending` ou `free`. */
                'state' => match (true) {
                    $day->lessThan(today()) => 'past',
                    $pending && ($accepted || $isBlocked) => 'conflict',
                    $accepted => 'accepted',
                    $isBlocked => 'blocked',
                    $pending => 'pending',
                    default => 'free',
                },
                /** Le point des visites du jour : `success` (une acceptée), `warning` (en attente), ou `null`. */
                'dot' => match (true) {
                    $visits->contains('status', BookingStatusEnum::ACCEPTED) => 'success',
                    $visits->isNotEmpty() => 'warning',
                    default => null,
                },
            ];
        }

        $last = today()->addDays(Booking::MAX_DAYS)->startOfMonth();

        return [
            'title' => __('offer::booking.calendar.title', ['offer' => $this->offerTitle($offer, $locale)], $locale),
            'month' => $from->format('Y-m'),
            'label' => ucfirst($from->copy()->locale($locale)->isoFormat('MMMM YYYY')),
            'previous' => $from->greaterThan($current->copy()->subMonths(self::PAST_MONTHS)) ? $from->copy()->subMonth()->format('Y-m') : null,
            'next' => $from->lessThan($last) ? $to->format('Y-m') : null,
            'weekdays' => $this->weekdays($locale),
            'offset' => $from->dayOfWeekIso - 1,
            'days' => $days,
            'legend' => $this->legend($offer, $locale),
        ];
    }

    /**
     * Le panneau d'un jour : ses demandes en attente ou acceptées, en cartes avec
     * Accepter / Refuser ; « Bloquer ce jour » (libre, à venir, sans séjour accepté)
     * ou « Débloquer ».
     *
     * @return array{title: string, items: list<array<string, mixed>>, empty: string, action: array{label: string, style: string, confirm: string|null, submission: array<string, mixed>}|null, note: string|null}
     */
    public function day(Offer $offer, Carbon $day, Authenticatable $owner, string $locale): array
    {
        $listing = new ReceivedBookingListing($owner);
        $bookings = $this->bookings($offer, $day, $day->copy()->addDay())
            ->filter(fn (Booking $booking): bool => $booking->type === BookingTypeEnum::VISIT ? $booking->check_in->isSameDay($day) : $this->coversNight($booking, $day));
        $isBlocked = $offer->blockedDates()->whereDate('date', $day->toDateString())->exists();
        $hasAccepted = $bookings->contains(fn (Booking $booking): bool => $booking->type === BookingTypeEnum::STAY && $booking->status === BookingStatusEnum::ACCEPTED);
        $parameters = ['offer' => $offer->getKey(), 'date' => $day->toDateString()];

        $action = null;
        $note = null;
        if (! $day->lessThan(today())) {
            if ($isBlocked) {
                $action = $this->action(__('offer::booking.calendar.unblock', [], $locale), 'default', null, Submission::toRoute('api.v1.offers.blocked-dates.destroy', SuccessAction::BACK, __('offer::booking.calendar.unblocked', [], $locale), parameters: $parameters));
            } elseif ($hasAccepted) {
                $note = __('offer::booking.calendar.block_accepted', [], $locale);
            } else {
                $action = $this->action(__('offer::booking.calendar.block', [], $locale), 'destructive', __('offer::booking.calendar.block_confirm', [], $locale), Submission::toRoute('api.v1.offers.blocked-dates.save', SuccessAction::BACK, __('offer::booking.calendar.blocked', [], $locale), parameters: $parameters));
            }
        }

        return [
            'title' => ucfirst($day->copy()->locale($locale)->isoFormat('dddd LL')).($isBlocked ? ' · '.__('offer::booking.calendar.legend.blocked', [], $locale) : ''),
            'items' => $bookings->map(fn (Booking $booking): array => $listing->item($booking)->toArray())->values()->all(),
            'empty' => __('offer::booking.calendar.empty_day', [], $locale),
            'action' => $action,
            'note' => $note,
        ];
    }

    /**
     * Les demandes en attente ou acceptées qui touchent `[$from, $to[`.
     *
     * @return Collection<int, Booking>
     */
    private function bookings(Offer $offer, Carbon $from, Carbon $to): Collection
    {
        return $offer->bookings()
            ->whereIn('status', [BookingStatusEnum::PENDING, BookingStatusEnum::ACCEPTED])
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $stays) => $stays->overlapping($from, $to))
                ->orWhere(fn (Builder $visits) => $visits->where('type', BookingTypeEnum::VISIT)
                    ->whereDate('check_in', '>=', $from->toDateString())
                    ->whereDate('check_in', '<', $to->toDateString())))
            ->with(['user', 'offer'])
            ->orderBy('check_in')
            ->get();
    }

    /**
     * @return list<string>
     */
    private function blocked(Offer $offer, Carbon $from, Carbon $to): array
    {
        return $offer->blockedDates()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<', $to->toDateString())
            ->pluck('date')
            ->map(fn (Carbon $date): string => $date->toDateString())
            ->values()
            ->all();
    }

    private function coversNight(Booking $booking, Carbon $day): bool
    {
        return $booking->check_out !== null && ! $booking->check_in->greaterThan($day) && $booking->check_out->greaterThan($day);
    }

    /**
     * Lundi à dimanche, abrégés dans la langue (« lu », « ma »…).
     *
     * @return list<string>
     */
    private function weekdays(string $locale): array
    {
        $monday = today()->startOfWeek(Carbon::MONDAY);

        return array_map(fn (int $offset): string => ucfirst($monday->copy()->addDays($offset)->locale($locale)->isoFormat('dd')), range(0, 6));
    }

    /**
     * Les couleurs de la grille, selon ce que l'offre reçoit : séjours ou visites.
     *
     * @return list<array{kind: string, tone: string, label: string}>
     */
    private function legend(Offer $offer, string $locale): array
    {
        $label = fn (string $key): string => __('offer::booking.calendar.legend.'.$key, [], $locale);
        $type = app(DomainRegistry::class)->require()->bookingType($offer);
        $entries = $type === BookingTypeEnum::VISIT
            ? [['dot', 'success', $label('visit_accepted')], ['dot', 'warning', $label('visit_pending')]]
            : [['day', 'accepted', $label('accepted')], ['day', 'pending', $label('pending')], ['day', 'conflict', $label('conflict')]];

        return array_map(fn (array $entry): array => ['kind' => $entry[0], 'tone' => $entry[1], 'label' => $entry[2]], [...$entries, ['day', 'blocked', $label('blocked')]]);
    }

    private function offerTitle(Offer $offer, string $locale): string
    {
        return app(DomainRegistry::class)->require()->itemTitle($offer) ?? (string) $offer->getTranslation('title', $locale);
    }

    /**
     * @return array{label: string, style: string, confirm: string|null, submission: array<string, mixed>}
     */
    private function action(string $label, string $style, ?string $confirm, Submission $submission): array
    {
        return ['label' => $label, 'style' => $style, 'confirm' => $confirm, 'submission' => $submission->toArray()];
    }
}
