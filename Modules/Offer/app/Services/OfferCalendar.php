<?php

namespace Modules\Offer\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Submission;
use Modules\Notification\Support\ActorData;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Lists\ReceivedBookingListing;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\BookingRequestAccepted;
use Modules\Offer\Notifications\BookingRequestCancelled;
use Modules\Offer\Notifications\BookingRequestReceived;
use Modules\Offer\Notifications\BookingRequestRefused;
use Modules\Offer\Notifications\OfferNotificationFilter;
use Modules\Offer\Support\BookingText;

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
    /** Le type de notification qui nomme chaque statut, pour la pastille d'une carte. */
    private const STEPS = [
        'pending' => BookingRequestReceived::class,
        'accepted' => BookingRequestAccepted::class,
        'refused' => BookingRequestRefused::class,
        'cancelled' => BookingRequestCancelled::class,
    ];

    /** Les mois qu'on peut revoir avant le mois courant. */
    public const PAST_MONTHS = 12;

    /**
     * @return array{title: string, heading: string, address: string|null, indication: string|null, month: string, label: string, previous: string|null, next: string|null, weekdays: list<string>, offset: int, days: list<array{date: string, day: int, state: string, dot: string|null}>, legend: list<array{kind: string, tone: string, label: string}>}
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
            $visits = $bookings->filter(fn (Booking $booking): bool => $booking->type === BookingTypeEnum::VISIT && $this->visitsOn($booking, $day));
            $isBlocked = in_array($date, $blocked, true);
            // Une visite colore son jour comme un séjour ses nuits (décision 2026-11-20-calendrier-visites).
            $stayAccepted = $stays->contains('status', BookingStatusEnum::ACCEPTED);
            $stayPending = $stays->contains('status', BookingStatusEnum::PENDING);
            $visitPending = $visits->contains('status', BookingStatusEnum::PENDING);
            $accepted = $stayAccepted || $visits->contains('status', BookingStatusEnum::ACCEPTED);
            $pending = $stayPending || $visitPending;
            // Chevauchement : un séjour en attente sur une nuit prise ou bloquée ; une visite en attente sur un jour bloqué.
            $conflict = ($stayPending && ($stayAccepted || $isBlocked)) || ($visitPending && $isBlocked);

            $days[] = [
                'date' => $date,
                'day' => (int) $day->day,
                /** `past`, `conflict`, `accepted`, `blocked`, `pending` ou `free` ; pour un séjour ses nuits, pour une visite son jour. */
                'state' => match (true) {
                    $day->lessThan(today()) => 'past',
                    $conflict => 'conflict',
                    $accepted => 'accepted',
                    $isBlocked => 'blocked',
                    $pending => 'pending',
                    default => 'free',
                },
                /** Plus de point : une visite colore son jour (gardé pour les anciennes versions de l'app). */
                'dot' => null,
            ];
        }

        $last = today()->addDays(Booking::MAX_DAYS)->startOfMonth();

        return [
            /** Titre de la barre du haut (« Calendrier »). */
            'title' => __('offer::booking.calendar.title', [], $locale),
            /** Titre du contenu : le titre saisi de l'offre (« Studio photos »). */
            'heading' => $this->offerTitle($offer, $locale),
            /** Son adresse sous le titre (« Rue Njo-Njo, Bonapriso, 00237 Douala, Cameroun »), ou `null`. */
            'address' => $this->offerAddress($offer, $locale),
            /** Les indications de l'adresse, dessous (« Portail vert, 2e étage »), ou `null`. */
            'indication' => $offer->indication($locale),
            'month' => (string) $from->format('Y-m'),
            'label' => ucfirst($from->copy()->locale($locale)->isoFormat('MMMM YYYY')),
            'previous' => $from->greaterThan($current->copy()->subMonths(self::PAST_MONTHS)) ? (string) $from->copy()->subMonth()->format('Y-m') : null,
            'next' => $from->lessThan($last) ? (string) $to->format('Y-m') : null,
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
     * @return array{title: string, items: list<array{id: string, actor: array<string, mixed>, tag: array{label: string, tone: string}|null, summary: string, text_links: list<array{text: string, link: string}>, message: string|null, total: string|null, created_at: string|null, actions: list<array<string, mixed>>}>, empty: string, action: array{label: string, style: string, confirm: string|null, submission: array<string, mixed>}|null, note: string|null}
     */
    public function day(Offer $offer, Carbon $day, Authenticatable $owner, string $locale): array
    {
        $listing = new ReceivedBookingListing($owner);
        $bookings = $this->bookings($offer, $day, $day->copy()->addDay())
            ->filter(fn (Booking $booking): bool => $booking->type === BookingTypeEnum::VISIT ? $this->visitsOn($booking, $day) : $this->coversNight($booking, $day));
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
            'items' => $this->cards($listing, $bookings, $locale),
            'empty' => __('offer::booking.calendar.empty_day', [], $locale),
            'action' => $action,
            'note' => $note,
        ];
    }

    /**
     * Les demandes en cartes de notification (décision 2026-11-20-calendrier-cartes) :
     * - qui demande (photo, nom, en ligne) et la date de la demande ;
     * - la pastille (« Demande de logement en attente », comme le type de notification) ;
     * - le texte (dates, durée, prix, voyageurs) et le message ;
     * - le total en gras (logement meublé) ;
     * - Accepter et Refuser, comme dans « Demandes reçues ».
     *
     * @param  Collection<int, Booking>  $bookings
     * @return list<array{id: string, actor: array<string, mixed>, tag: array{label: string, tone: string}|null, summary: string, text_links: list<array{text: string, link: string}>, message: string|null, total: string|null, created_at: string|null, actions: list<array<string, mixed>>}>
     */
    private function cards(ReceivedBookingListing $listing, Collection $bookings, string $locale): array
    {
        $current = app()->getLocale();
        app()->setLocale($locale);
        $tags = app(OfferNotificationFilter::class);

        $cards = [];
        foreach ($bookings as $booking) {
            $summary = BookingText::summary($booking, 'requested');
            /** @var list<array<string, mixed>> $actions */
            $actions = $listing->item($booking)->toArray()['actions'];
            $tag = $tags->tag(self::STEPS[$booking->status->value] ?? BookingRequestReceived::class);
            $message = $booking->message;
            $total = $booking->formattedTotal($locale);
            $cards[] = [
                'id' => (string) $booking->getKey(),
                'actor' => ActorData::from($booking->user),
                'tag' => $tag === null ? null : ['label' => (string) $tag['label'], 'tone' => (string) $tag['tone']],
                'summary' => $summary,
                'text_links' => BookingText::offerLink($booking, $summary),
                'message' => $message === null ? null : (string) $message,
                'total' => $total === null ? null : (string) $total,
                'created_at' => $booking->created_at === null ? null : (string) $booking->created_at->toIso8601String(),
                'actions' => $actions,
            ];
        }
        app()->setLocale($current);

        return $cards;
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
                    ->where(fn (Builder $dates) => $dates
                        ->where(fn (Builder $first) => $first->whereDate('check_in', '>=', $from->toDateString())->whereDate('check_in', '<', $to->toDateString()))
                        ->orWhere(fn (Builder $second) => $second->whereDate('check_out', '>=', $from->toDateString())->whereDate('check_out', '<', $to->toDateString())))))
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

    /** Une visite tombe ce jour-là : sa date, ou la seconde date proposée. */
    private function visitsOn(Booking $booking, Carbon $day): bool
    {
        return $booking->check_in->isSameDay($day) || ($booking->check_out?->isSameDay($day) ?? false);
    }

    private function coversNight(Booking $booking, Carbon $day): bool
    {
        return $booking->type === BookingTypeEnum::STAY && $booking->check_out !== null && ! $booking->check_in->greaterThan($day) && $booking->check_out->greaterThan($day);
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
        // Le même style pour les visites que pour les séjours : des jours colorés.
        $entries = $type === BookingTypeEnum::VISIT
            ? [['day', 'accepted', $label('visit_accepted')], ['day', 'pending', $label('visit_pending')], ['day', 'conflict', $label('conflict')]]
            : [['day', 'accepted', $label('accepted')], ['day', 'pending', $label('pending')], ['day', 'conflict', $label('conflict')]];

        return array_map(fn (array $entry): array => ['kind' => $entry[0], 'tone' => $entry[1], 'label' => $entry[2]], [...$entries, ['day', 'blocked', $label('blocked')]]);
    }

    /** Le titre saisi par le propriétaire, dans la langue (sa langue de saisie à défaut). */
    private function offerTitle(Offer $offer, string $locale): string
    {
        return (string) $offer->getTranslation('title', $locale);
    }

    private function offerAddress(Offer $offer, string $locale): ?string
    {
        $current = app()->getLocale();
        app()->setLocale($locale);
        $address = $offer->fullAddress();
        app()->setLocale($current);

        return $address;
    }

    /**
     * @return array{label: string, style: string, confirm: string|null, submission: array<string, mixed>}
     */
    private function action(string $label, string $style, ?string $confirm, Submission $submission): array
    {
        return ['label' => $label, 'style' => $style, 'confirm' => $confirm, 'submission' => $submission->toArray()];
    }
}
