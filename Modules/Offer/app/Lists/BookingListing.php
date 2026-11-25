<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Lists\Concerns\FiltersBookings;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * « Mes réservations » (décision 2026-11-19-contrat-reservations) : les demandes du
 * membre dans le pays courant, la plus récente en tête ; l'offre, les dates, le
 * statut et le total ; « Voir » ouvre la fiche, « Annuler la demande » tant
 * qu'elle peut l'être.
 */
class BookingListing extends Listing
{
    use FiltersBookings;

    public static function name(): string
    {
        return 'offer.bookings';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function title(): string
    {
        return __('offer::booking.list.mine');
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

        return ['title' => trans_choice('offer::booking.list.count', $count, ['count' => $count]), 'subtitle' => null];
    }

    public function emptyText(): string
    {
        return __('offer::booking.list.mine_empty');
    }

    public function searchPlaceholder(): string
    {
        // « Rechercher… », le même partout (décision 2026-11-22-recherche-placeholder-unique).
        return __('form::mobile.form.search');
    }

    /**
     * « Rechercher un logement » dans la barre fixe en bas : retour à l'accueil
     * (décision 2026-11-19-contrat-reservations-logements-profil).
     */
    public function footer(): ?array
    {
        return ['label' => __('offer::booking.profile.search_home'), 'form' => null, 'link' => '/search'];
    }

    /**
     * Les logements qu'il a demandés dans le pays courant, supprimés compris.
     *
     * @return Builder<Offer>
     */
    protected function bookedOffers(): Builder
    {
        return Offer::query()->withTrashed()
            ->whereIn('id', Booking::query()->select('offer_id')->where('user_id', $this->user?->getAuthIdentifier()))
            ->where('country_id', CurrentCountry::require()->getKey());
    }

    /**
     * @return Builder<Booking>
     */
    public function query(): Builder
    {
        return Booking::query()
            // La note et le nombre d'avis du logement, comme à l'accueil (décision 2026-11-21-reservations-note).
            // Et s'il est en favori, pour le cœur de la carte (décision 2026-11-22-reservations-coeur).
            ->with(['offer' => fn ($offer) => $offer->with(['media', 'address', 'country'])->withReviewSummary()->withFavoriteOf($this->user?->getAuthIdentifier())])
            // Sa dernière notification du fil de la demande, reçue ou de lui : la carte ouvre sa page, même tant que le
            // propriétaire n'a pas répondu (décisions 2026-11-21-reservation-comme-notification et
            // 2026-11-24-reservations-lien-demande-envoyee).
            ->addSelect(['bookings.*', 'thread_notification_id' => Booking::latestVisibleNotification($this->user?->getAuthIdentifier())])
            // Ses notifications non lues, pour le compteur de la carte (décision 2026-11-22-reservations-non-lues).
            ->addSelect(['unread_notifications_count' => DatabaseNotification::query()
                ->selectRaw('count(*)')
                ->where('notifiable_type', (new User)->getMorphClass())
                ->where('notifiable_id', $this->user?->getAuthIdentifier())
                ->whereNull('read_at')
                ->whereColumn('data->booking_id', 'bookings.id')])
            ->where('user_id', $this->user?->getAuthIdentifier())
            ->whereHas('offer', fn (Builder $offer) => $offer->withTrashed()->where('country_id', CurrentCountry::require()->getKey()))
            ->orderByDesc('id');
    }

    protected function search(Builder $query, string $term): void
    {
        $query->orWhereHas('offer', fn (Builder $offer) => $offer->withTrashed()->where(
            fn (Builder $columns) => $this->orWhereContains($columns, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term),
        ));
    }

    public function item(Model $record): ListItem
    {
        /** @var Booking $record */
        $locale = $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();

        // La page de sa demande, comme celle d'une notification : suivi, lignes, conversation ; sans
        // notification dans son fil (une demande d'avant), la fiche du logement
        // (décision 2026-11-21-reservation-comme-notification).
        $notification = $record->getAttribute('thread_notification_id');
        $item = BookingItem::make($record, $locale, mine: true)
            ->link(is_string($notification) && $notification !== '' ? '/notifications/'.$notification : '/offers/'.$record->offer_id);
        // Le statut sur la photo, en haut à gauche, le cœur à droite (décision 2026-11-21-reservations-statut-coeur).
        $item->tag(...[...$record->statusTag(), 'onImage' => true]);
        // Le titre saisi de l'offre en lien vers sa fiche : le sous-titre, sous la ligne du type ; sans ligne du type,
        // le titre lui-même. Le reste de la carte ouvre la page de la demande (décision 2026-11-22-reservations-lien-offre).
        OfferCardTitle::of($record->offer, $locale)[1] === null
            ? $item->titleLink('/offers/'.$record->offer_id)
            : $item->subtitleLink('/offers/'.$record->offer_id);
        // Le cœur des favoris à la place du menu trois points : « Modifier » et « Annuler » sont sur la page de la
        // demande (décision 2026-11-22-reservations-coeur).
        $item->favorite((bool) $record->offer->is_favorite, $record->offer->favoriteEndpoint());

        // Ses notifications non lues : un compteur en bas de la carte, qui ouvre la page de la demande, comme dans
        // « Mes logements » (décision 2026-11-22-reservations-non-lues).
        $unread = (int) ($record->getAttribute('unread_notifications_count') ?? 0);
        if ($unread > 0 && is_string($notification) && $notification !== '') {
            $item->counter(trans_choice('offer::booking.profile.unread_count', $unread, ['count' => $unread], $locale), 'notifications', link: '/notifications/'.$notification);
        }

        return $item;
    }
}
