<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Forms\OfferForm;
use Modules\Offer\Lists\Concerns\FiltersOffers;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\OfferNotificationFilter;

/**
 * Les offres du compte (« Mes logements »), ouvertes depuis le menu du profil :
 * titre, statut et prix ; recherche dans le titre ; création, édition et retrait
 * (décision 2026-11-18-offres-et-domaines). Sans domaine choisi, la liste
 * n'existe pas.
 */
class OfferListing extends Listing
{
    use FiltersOffers;

    public static function name(): string
    {
        return 'offer.mine';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function title(): string
    {
        return app(DomainRegistry::class)->require()->mineLabel();
    }

    /**
     * Sous les filtres, en titre, le nombre de la liste, recherche et filtres compris, comme dans « Demandes
     * reçues » (décision 2026-11-22-listes-nombre-en-tete).
     *
     * @return array{title: string|null, subtitle: string|null}
     */
    public function headingFor(array $filters, ?string $search = null): array
    {
        $count = $this->countFor($search, $filters);

        return ['title' => trans_choice('offer::offer.list.count', $count, ['count' => $count]), 'subtitle' => null];
    }

    public function emptyText(): string
    {
        return __('offer::offer.list.empty');
    }

    /**
     * Voir, Éditer, Calendrier, Retirer : dans un menu déroulant (décision 2026-11-21-actions-en-menu).
     */
    public function actionsMenu(): bool
    {
        return true;
    }

    /**
     * « Ajouter un logement » dans la barre fixe en bas, plus sous la recherche
     * (décision 2026-11-19-contrat-reservations-logements-profil). Pas proposé à qui
     * ne peut pas créer, ni au-delà d'une offre quand le domaine l'impose.
     */
    public function footer(): ?array
    {
        $domain = app(DomainRegistry::class)->require();

        if ($this->user === null || ! $this->user->can('create', Offer::class)
            || ($domain->onePerAccount() && Offer::query()->where('user_id', $this->user->getAuthIdentifier())->where('country_id', CurrentCountry::require()->getKey())->exists())) {
            return null;
        }

        return ['label' => $domain->addLabel(), 'form' => OfferForm::name(), 'link' => null];
    }

    /**
     * Le statut de l'annonce, puis les filtres de l'accueil, parmi ses propres offres
     * (décision 2026-11-19-contrat-reservations-logements-profil).
     */
    public function filters(): array
    {
        return [
            Field::select('status')
                ->label(__('offer::booking.profile.offer_status'))
                ->options(OfferStatusEnum::options()),
            // Les logements qui ont au moins une demande dans ce statut : à venir, en attente, passée.
            BookingFilters::statusSelect('booking_status', __('offer::booking.profile.filter_booking_status')),
            ...$this->offerFilters($this->mine()),
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $group = BookingFilters::group($filters, 'booking_status');
        if ($group !== null) {
            $query->whereHas('bookings', fn (Builder $bookings) => $bookings->inStatusGroup($group));
        }
        $this->applyOfferFilters($query, $filters);
    }

    /**
     * Ses offres dans le pays courant.
     *
     * @return Builder<Offer>
     */
    private function mine(): Builder
    {
        return Offer::query()
            ->where('user_id', $this->user?->getAuthIdentifier())
            ->where('country_id', CurrentCountry::require()->getKey());
    }

    public function searchPlaceholder(): string
    {
        // « Rechercher… », le même partout (décision 2026-11-22-recherche-placeholder-unique).
        return __('form::mobile.form.search');
    }

    /**
     * @return Builder<Offer>
     */
    public function query(): Builder
    {
        // Les siennes, dans le pays courant (décision 2026-11-18-espace-pays).
        return $this->mine()
            ->with(['media', 'address'])
            // Moyenne, nombre et dernier avis (décision 2026-11-19-contrat-avis-offres).
            ->withReviewSummary()
            // Les demandes en attente, pour le compteur de la carte.
            // Sans les expirées (décision 2026-11-22-statuts-terminee-expiree).
            ->withCount(['bookings as pending_bookings_count' => fn (Builder $bookings) => $bookings->awaitingAnswer()])
            // Ses notifications non lues de ce logement, quel que soit le statut de la demande, une demande (un fil)
            // comptant pour une, pour le second compteur (décision 2026-11-21-compteur-non-lues-sans-statut).
            ->addSelect(['unread_notifications_count' => DatabaseNotification::query()
                ->selectRaw('count(distinct thread)')
                ->where('notifiable_type', (new User)->getMorphClass())
                ->where('notifiable_id', $this->user?->getAuthIdentifier())
                ->whereNull('read_at')
                ->whereNotNull('thread')
                ->whereColumn('data->offer_id', 'offers.id')])
            ->orderBy('id');
    }

    protected function search(Builder $query, string $term): void
    {
        // Le titre, toutes langues confondues.
        $this->orWhereContains($query, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term);
    }

    public function item(Model $record): ListItem
    {
        /** @var Offer $record */
        // « 60 000 FCFA / jour » : le prix et son unité, selon le domaine.
        $price = app(DomainRegistry::class)->require()->formattedPrice($record, app()->getLocale());

        $locale = $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();

        // Le titre saisi ; dessous, la ligne du type (« Location meublée · Studio · Douala »), comme à
        // l'accueil (décision 2026-11-21-cartes-titre-saisi-ligne-type).
        $domain = app(DomainRegistry::class)->require();
        [$title, $line] = OfferCardTitle::composedFirst($record, $locale);

        // La date d'expiration, après le statut (décision 2026-11-19-expiration-mes-logements) :
        // « Expire le 30 juin 2027 », « Expirée le 2 janvier 2026 » ; sans date, rien.
        $expiration = $record->expires_at === null ? null : __(
            $record->expires_at->isFuture() ? 'offer::offer.list.expires' : 'offer::offer.list.expired',
            ['date' => $record->expires_at->copy()->locale($locale)->isoFormat('LL')],
            $locale,
        );

        // En gras, « Publiée · 80 000 FCFA / mois » (décision 2026-11-19-item-titre-saisi-details).
        // La note, tant qu'elle est publiée.
        $item = ListItem::make($record->getKey(), $title, $line)
            ->image(MediaUrl::onRequestHost($record->photoUrl()))
            ->rating($record->showsReviews() ? $record->reviews_avg_rating : null, (int) $record->reviews_count)
            // Le statut en pastille de couleur (décision 2026-11-19-statut-carte-logement), sur la photo à la
            // place du cœur : le propriétaire ne met pas son logement en favori (décision 2026-11-21-statut-a-la-place-du-coeur).
            ->tag($record->status->label(), $record->status->tone(), onImage: true)
            ->highlight(implode(' · ', array_filter([$expiration, ...$domain->itemDetails($record), $price])))
            ->edit(OfferForm::name())
            // Toucher la carte ouvre sa fiche, brouillon compris ; « Éditer » est dans le menu (décision
            // 2026-11-22-logements-carte-vers-fiche).
            ->link('/offers/'.$record->getKey())
            // Son calendrier : occupation, demandes, jours bloqués (décision 2026-11-19-contrat-calendrier-offre).
            ->action(__('offer::booking.calendar.button'), link: '/offers/'.$record->getKey().'/calendar')
            // Puis « Demandes » : la liste des demandes reçues, comme « Mes réservations », ce logement déjà choisi
            // dans le filtre « Logement » (décision 2026-11-21-demandes-comme-mes-reservations).
            ->action(
                __('offer::booking.profile.notifications'),
                filter: ['offer' => (string) $record->getKey()],
                list: ReceivedBookingListing::name(),
            );
        // Plus de « Retirer » sur la carte : il est dans la barre du bas de la fiche (décision 2026-11-22-retirer-depuis-la-fiche).

        // Ses demandes en attente : un compteur (décision 2026-11-19-contrat-reservations-logements-profil).
        $pending = (int) ($record->pending_bookings_count ?? 0);

        // Il ouvre les notifications, filtrées sur les demandes de logement réellement en attente de ce logement
        // (décision 2026-11-20-compteur-vers-notifications), par-dessus les onglets, avec un retour vers la carte
        // (`/notifications/filtered`, décision 2026-11-21-notifications-avec-retour).
        if ($pending > 0) {
            $item->counter(
                trans_choice('offer::booking.profile.pending_count', $pending, ['count' => $pending]),
                'notifications',
                ['type' => OfferNotificationFilter::BOOKING, 'status' => BookingStatusEnum::PENDING->value, 'offer' => (string) $record->getKey()],
                link: '/notifications/filtered',
                // Une pastille orange, comme « En attente », sous le titre (décision 2026-11-21-pastille-demandes-en-attente).
                tone: BookingStatusEnum::PENDING->tone(),
            );
        }

        // Ses notifications non lues : un second compteur, demandes de ce logement, non lues, sans filtre de statut
        // (décision 2026-11-21-compteur-non-lues-sans-statut).
        $unread = (int) ($record->unread_notifications_count ?? 0);
        if ($unread > 0) {
            $item->counter(
                trans_choice('offer::booking.profile.unread_count', $unread, ['count' => $unread]),
                'notifications',
                ['type' => OfferNotificationFilter::BOOKING, 'offer' => (string) $record->getKey(), 'unread' => 'yes'],
                link: '/notifications/filtered',
            );
        }

        return $item;
    }
}
