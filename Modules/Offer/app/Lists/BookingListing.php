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
use Modules\Offer\Forms\BookingCancelForm;
use Modules\Offer\Forms\BookingEditForm;
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

    public function emptyText(): string
    {
        return __('offer::booking.list.mine_empty');
    }

    public function searchPlaceholder(): string
    {
        return __('offer::booking.list.search');
    }

    /**
     * « Rechercher un logement » dans la barre fixe en bas : retour à l'accueil
     * (décision 2026-11-19-contrat-reservations-logements-profil).
     */
    /**
     * Voir, Annuler la demande : dans le menu trois points, comme « Mes logements »
     * (décision 2026-11-21-reservations-menu).
     */
    public function actionsMenu(): bool
    {
        return true;
    }

    public function footer(): ?array
    {
        return ['label' => __('offer::booking.profile.search_home'), 'form' => null, 'link' => '/'];
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
            ->with(['offer' => fn ($offer) => $offer->with(['media', 'address'])->withReviewSummary()])
            // Sa dernière notification du fil de la demande : la carte ouvre sa page (décision
            // 2026-11-21-reservation-comme-notification).
            ->addSelect(['bookings.*', 'thread_notification_id' => DatabaseNotification::query()
                ->select('id')
                ->where('notifiable_type', (new User)->getMorphClass())
                ->where('notifiable_id', $this->user?->getAuthIdentifier())
                ->whereColumn('data->booking_id', 'bookings.id')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(1)])
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
        // Le statut sur la photo, comme dans « Mes logements » ; à droite, le menu des actions à la place du
        // cœur (décisions 2026-11-21-reservations-statut-coeur et 2026-11-21-reservations-menu).
        $item->tag($record->status->label(), $record->status->tone(), onImage: true);
        // « Modifier », dans le menu, tant qu'elle est en attente (décision 2026-11-21-modifier-reservation).
        if ($record->isAwaitingAnswer()) {
            $item->action(__('offer::booking.form.edit'), form: BookingEditForm::name(), record: $record->getKey());
        }

        // « Annuler », en dernier, tant qu'elle peut l'être : il ouvre le formulaire d'annulation, comme le bouton
        // « Annuler » de la page de la demande (décisions 2026-11-21-annuler-depuis-le-detail et
        // 2026-11-22-reservations-menu-annuler).
        if ($record->isCancellable()) {
            $item->action(__('offer::booking.cancel.button'), 'destructive', form: BookingCancelForm::name(), record: $record->getKey());
        }

        return $item;
    }
}
