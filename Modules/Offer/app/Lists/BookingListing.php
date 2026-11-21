<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Field;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Form\Support\Submission;
use Modules\Location\Models\Address;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\OfferAddress;
use Modules\Offer\Notifications\OfferNotificationFilter;

/**
 * « Mes réservations » (décision 2026-11-19-contrat-reservations) : les demandes du
 * membre dans le pays courant, la plus récente en tête ; l'offre, les dates, le
 * statut et le total ; « Voir » ouvre la fiche, « Annuler la demande » tant
 * qu'elle peut l'être.
 */
class BookingListing extends Listing
{
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
     * Voir, Notifications, Annuler la demande : dans le menu trois points, comme « Mes logements »
     * (décision 2026-11-21-reservations-comme-logements).
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
     * Statut (à venir, en attente, passées), ville du logement, type (séjour, visite).
     */
    public function filters(): array
    {
        return [
            BookingFilters::status(),
            Field::select('city')
                ->label(__('offer::booking.profile.filter_city'))
                ->options($this->cities()),
            Field::select('type')
                ->label(__('offer::booking.profile.filter_type'))
                ->options(BookingTypeEnum::options()),
        ];
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        BookingFilters::applyStatus($query, $filters);
        if (isset($filters['city'])) {
            $query->whereHas('offer', fn (Builder $offer) => $offer->withTrashed()->whereHas('address', fn (Builder $address) => $address->where('city', $filters['city'])));
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
    }

    /**
     * Les villes des logements qu'il a demandés.
     *
     * @return array<string, string>
     */
    private function cities(): array
    {
        return Address::query()
            ->whereIn('id', OfferAddress::query()->select('address_id')->whereIn('offer_id', Booking::query()->select('offer_id')->where('user_id', $this->user?->getAuthIdentifier())))
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city', 'city')
            ->all();
    }

    /**
     * @return Builder<Booking>
     */
    public function query(): Builder
    {
        return Booking::query()
            ->with(['offer.media', 'offer.address'])
            // Ses notifications non lues de cette demande, pour le compteur de la carte (décision 2026-11-21-reservations-comme-logements).
            ->addSelect(['bookings.*', 'unread_notifications_count' => DatabaseNotification::query()
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

        $item = BookingItem::make($record, $locale)->link('/offers/'.$record->offer_id);
        // Comme une carte de « Mes logements » (décision 2026-11-21-reservations-comme-logements) : le statut
        // sur la photo, ses notifications (toutes, et les non lues en compteur), les actions dans le menu.
        $item->tag($record->status->label(), $record->status->tone(), onImage: true);
        $filter = ['type' => OfferNotificationFilter::BOOKING, 'offer' => (string) $record->offer_id];
        $item->action(__('offer::booking.profile.notifications'), link: '/notifications/filtered', filter: $filter, list: 'notifications');
        $unread = (int) ($record->unread_notifications_count ?? 0);
        if ($unread > 0) {
            $item->counter(
                trans_choice('offer::booking.profile.unread_count', $unread, ['count' => $unread]),
                'notifications',
                [...$filter, 'unread' => 'yes'],
                link: '/notifications/filtered',
            );
        }

        return ! $record->isCancellable() ? $item : $item->delete(
            __('offer::booking.cancel.label'),
            __('offer::booking.cancel.confirm'),
            Submission::toRoute(
                'api.v1.bookings.cancel',
                SuccessAction::BACK,
                __('offer::booking.cancel.done'),
                destructive: true,
                parameters: ['booking' => $record->getKey()],
            ),
        );
    }
}
