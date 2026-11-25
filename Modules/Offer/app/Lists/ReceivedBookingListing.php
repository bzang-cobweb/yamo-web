<?php

namespace Modules\Offer\Lists;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Forms\BookingAcceptForm;
use Modules\Offer\Forms\BookingRefuseForm;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

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
        // « En attente » : sans les expirées (décision 2026-11-22-statuts-terminee-expiree).
        if ($status === BookingStatusEnum::PENDING) {
            $query->awaitingAnswer();
        } elseif ($status !== null) {
            $query->where('status', $status);
        }
        BookingFilters::applyPeriod($query, $filters);
        if (isset($filters['offer']) && is_scalar($filters['offer'])) {
            $query->where('offer_id', (string) $filters['offer']);
        }
    }

    /**
     * Pas de menu trois points : Accepter, Refuser, Masquer l'adresse en boutons sur la carte (décision
     * 2026-11-22-demandes-sans-menu).
     */
    public function actionsMenu(): bool
    {
        return false;
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
        // « Rechercher… », le même partout (décision 2026-11-22-recherche-placeholder-unique).
        return __('form::mobile.form.search');
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
            ->with(['offer.country', 'user'])
            // Sa dernière notification du fil de la demande : la carte ouvre sa page.
            ->addSelect(['bookings.*', 'thread_notification_id' => Booking::latestVisibleNotification($this->user?->getAuthIdentifier())])
            // La date de sa dernière notification, pour la carte (décision 2026-11-22-demandes-date-derniere-notification).
            ->addSelect(['last_notification_at' => DatabaseNotification::query()
                ->select('created_at')
                ->where('notifiable_type', (new User)->getMorphClass())
                ->where('notifiable_id', $this->user?->getAuthIdentifier())
                ->whereColumn('data->booking_id', 'bookings.id')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(1)])
            // Le dernier message du fil (le membre ou le propriétaire), pour la carte (décision
            // 2026-11-22-demandes-dernier-message).
            ->addSelect(['last_message' => DatabaseNotification::query()
                ->select('data->message')
                ->whereColumn('data->booking_id', 'bookings.id')
                ->whereNotNull('data->message')
                ->where('data->message', '!=', '')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(1)])
            // Ses notifications non lues, pour le compteur de la carte (décision 2026-11-21-demandes-non-lues).
            ->addSelect(['unread_notifications_count' => DatabaseNotification::query()
                ->selectRaw('count(*)')
                ->where('notifiable_type', (new User)->getMorphClass())
                ->where('notifiable_id', $this->user?->getAuthIdentifier())
                ->whereNull('read_at')
                ->whereColumn('data->booking_id', 'bookings.id')])
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
        // Le dernier message du fil, sa première ligne, cité sur la carte (décision 2026-11-22-demandes-dernier-message) ;
        // à défaut, celui de la demande.
        $message = $record->getAttribute('last_message');
        $message = is_string($message) && trim($message) !== '' ? $message : $record->message;
        $item = OfferRequestItem::make($record, $locale)
            ->link(is_string($notification) && $notification !== '' ? '/notifications/'.$notification : '/offers/'.$record->offer_id)
            ->quote(is_string($message) && trim($message) !== '' ? Str::of($message)->trim()->before("\n")->squish()->value() : null);
        // La date de sa dernière notification, à droite du nom (décisions 2026-11-22-demandes-date-derniere-notification et 2026-11-22-demandes-date-chiffres).
        $last = $record->getAttribute('last_notification_at');
        $item->date($last === null ? null : Carbon::parse((string) $last)->toIso8601String());

        // Ses notifications non lues : le nombre, en pastille à droite du message (décision
        // 2026-11-22-demandes-pastille-nombre-message).
        $item->unreadCount((int) ($record->getAttribute('unread_notifications_count') ?? 0));

        // Pas de bouton sur la carte : la toucher ouvre le détail de la demande, où l'on répond (décision
        // 2026-11-22-demandes-carte-seule).
        return $item;
    }

    /**
     * Les boutons d'une demande : « Accepter », « Refuser » tant qu'elle attend. Plus sur les cartes de la liste
     * (décision 2026-11-22-demandes-carte-seule), mais dans le panneau du jour du calendrier (décision
     * 2026-11-19-contrat-calendrier-offre), sans afficher ou masquer l'adresse (décision 2026-11-22-calendrier-sans-adresse).
     */
    public function withAnswerActions(ListItem $item, Booking $record, string $locale): ListItem
    {
        if ($record->isAwaitingAnswer()) {
            // « Accepter » et « Refuser » ouvrent chacun leur formulaire, avec un message facultatif ; celui d'« Accepter »
            // avertit des demandes qui chevauchent (décision 2026-11-22-accepter-avec-message).
            $item->action(__('offer::booking.profile.accept'), 'primary', form: BookingAcceptForm::name())
                ->action(__('offer::booking.profile.refuse'), 'destructive', form: BookingRefuseForm::name());
        }

        return $item;
    }
}
