<?php

namespace Modules\Offer\Lists;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Form\Support\Submission;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Forms\BookingRefuseForm;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\OfferAvailability;

/**
 * « Demandes » d'une offre (décision 2026-11-21-demandes-d-une-offre) : les demandes reçues sur un logement
 * du compte, ouvertes par « Demandes » de sa carte dans « Mes logements », la plus récente en tête. Une liste
 * à elle, qui ne reprend plus « Mes réservations » : sous les filtres, le titre du logement et sa ligne du
 * type ; des cartes sans le logement ni photo (type de demande et dates, statut) ; filtres : statut et
 * période, le logement en champ caché. « Répondre » tant qu'elle attend.
 */
class ReceivedBookingListing extends Listing
{
    public static function name(): string
    {
        return 'offer.bookings.received';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function title(): string
    {
        return __('offer::booking.profile.requests_all');
    }

    /**
     * Le logement choisi : son titre dans la barre du haut, à la place de « Demandes » (décision
     * 2026-11-21-demandes-titre-logement-barre) ; sans logement, « Demandes ».
     */
    public function titleFor(array $filters): string
    {
        $offer = $this->chosenOffer($filters);

        return $offer === null ? $this->title() : OfferCardTitle::of($offer, $this->locale())[0];
    }

    /**
     * Sous les filtres, le nombre de demandes de la liste, recherche et filtres compris : « 3 demandes »,
     * « Aucune demande » (décision 2026-11-21-demandes-nombre).
     *
     * @return array{title: string|null, subtitle: string|null}
     */
    public function headingFor(array $filters, ?string $search = null): array
    {
        $count = $this->countFor($search, $filters);

        // En titre, en gras (décision 2026-11-21-demandes-nombre-en-titre).
        return ['title' => trans_choice('offer::booking.profile.requests_count', $count, ['count' => $count], $this->locale()), 'subtitle' => null];
    }

    /**
     * Le statut réel, la période d'après les dates ; le logement en champ caché, choisi par « Demandes » de
     * sa carte (décision 2026-11-21-demandes-filtre-logement-cache).
     *
     * @return list<Field>
     */
    public function filters(): array
    {
        return [
            Field::select('status')
                ->label(__('offer::booking.profile.filter_status'))
                ->options(BookingStatusEnum::options()),
            BookingFilters::period(),
            Field::hidden('offer'),
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        $status = BookingStatusEnum::tryFrom(is_string($filters['status'] ?? null) ? $filters['status'] : '');
        if ($status !== null) {
            $query->where('status', $status);
        }
        BookingFilters::applyPeriod($query, $filters);
        if (isset($filters['offer']) && is_scalar($filters['offer'])) {
            $query->where('offer_id', (string) $filters['offer']);
        }
    }

    /**
     * Les actions dans le menu trois points.
     */
    public function actionsMenu(): bool
    {
        return true;
    }

    /**
     * Le logement des filtres, s'il est au compte et dans le pays courant (supprimé compris).
     *
     * @param  array<string, mixed>  $filters
     */
    private function chosenOffer(array $filters): ?Offer
    {
        if (! isset($filters['offer']) || ! is_scalar($filters['offer'])) {
            return null;
        }

        return Offer::query()->withTrashed()
            ->with('address')
            ->whereKey((string) $filters['offer'])
            ->where('user_id', $this->user?->getAuthIdentifier())
            ->where('country_id', CurrentCountry::require()->getKey())
            ->first();
    }

    private function locale(): string
    {
        return $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();
    }

    public function searchPlaceholder(): string
    {
        return __('offer::booking.list.search_requests');
    }

    /**
     * Le nom du membre seul (prénom, nom, pseudonyme) : il n'y a qu'un logement (décisions
     * 2026-11-21-demandes-nombre et 2026-11-21-demandes-recherche-nom).
     */
    protected function search(Builder $query, string $term): void
    {
        $query->orWhereHas('user', fn (Builder $user) => $user->where(fn (Builder $columns) => $this->orWhereContains($columns, ['first_name', 'last_name', 'name'], $term)));
    }

    public function emptyText(): string
    {
        return __('offer::booking.list.received_empty');
    }

    /**
     * @return Builder<Booking>
     */
    public function query(): Builder
    {
        return Booking::query()
            ->with(['offer', 'user'])
            // Sa dernière notification du fil de la demande : la carte ouvre sa page.
            ->addSelect(['bookings.*', 'thread_notification_id' => DatabaseNotification::query()
                ->select('id')
                ->where('notifiable_type', (new User)->getMorphClass())
                ->where('notifiable_id', $this->user?->getAuthIdentifier())
                ->whereColumn('data->booking_id', 'bookings.id')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(1)])
            ->whereHas('offer', fn (Builder $offer) => $offer->withTrashed()
                ->where('user_id', $this->user?->getAuthIdentifier())
                ->where('country_id', CurrentCountry::require()->getKey()))
            ->orderByDesc('id');
    }

    public function item(Model $record): ListItem
    {
        /** @var Booking $record */
        $locale = $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();
        // Sa carte à elle, sans le logement, déjà nommé sous les filtres ; elle ouvre la page de la demande, où se
        // lit son message ; sans notification dans son fil, la fiche du logement (décision 2026-11-21-demandes-d-une-offre).
        $notification = $record->getAttribute('thread_notification_id');
        $item = OfferRequestItem::make($record, $locale)
            ->link(is_string($notification) && $notification !== '' ? '/notifications/'.$notification : '/offers/'.$record->offer_id);

        if ($record->isAwaitingAnswer()) {
            // Sur la carte : « Accepter » après confirmation, « Refuser » avec un message
            // facultatif (décision 2026-11-19-contrat-reservations-logements-profil).
            $item->action(
                __('offer::booking.profile.accept'),
                'primary',
                $this->acceptConfirm($record, $locale),
                Submission::toRoute('api.v1.bookings.accept', SuccessAction::BACK, __('offer::booking.profile.accepted_notice'), parameters: ['booking' => $record->getKey()]),
            )
                ->action(__('offer::booking.profile.refuse'), 'destructive', form: BookingRefuseForm::name());
        }

        // Puis, quel que soit le statut, afficher ou masquer l'adresse au demandeur (décision 2026-11-21-afficher-adresse).
        return $item->action(
            __('offer::booking.profile.'.($record->show_address ? 'hide_address' : 'show_address')),
            submission: Submission::toRoute(
                'api.v1.bookings.address.'.($record->show_address ? 'hide' : 'show'),
                SuccessAction::BACK,
                __('offer::booking.profile.'.($record->show_address ? 'address_hidden' : 'address_shown')),
                parameters: ['booking' => $record->getKey()],
            ),
        );
    }

    /**
     * « Accepter la demande de Marie Curie (du 3 au 6 mars) ? », suivi des demandes
     * en attente qui la chevauchent et seront refusées (décision
     * 2026-11-19-contrat-calendrier-offre).
     */
    private function acceptConfirm(Booking $booking, string $locale): string
    {
        $confirm = __('offer::booking.profile.accept_confirm', ['guest' => (string) $booking->user->full_name, 'period' => $booking->period($locale)], $locale);
        $others = app(OfferAvailability::class)->overlappingPending($booking);
        if ($others->isEmpty()) {
            return $confirm;
        }

        $names = $others->map(fn (Booking $other): string => $other->user->full_name.' ('.$other->period($locale).')')->implode(', ');

        return $confirm.' '.trans_choice('offer::booking.profile.accept_overlaps', $others->count(), ['requests' => $names], $locale);
    }
}
