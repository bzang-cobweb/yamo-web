<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Form\Support\Submission;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Forms\BookingRefuseForm;
use Modules\Offer\Lists\Concerns\FiltersBookings;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Services\OfferAvailability;

/**
 * « Demandes » (décision 2026-11-19-contrat-reservations) : les demandes sur les offres du compte dans le
 * pays courant, la plus récente en tête ; « Répondre » tant qu'elle attend. Ouverte par « Demandes » d'une
 * carte de « Mes logements », le logement déjà choisi dans le filtre. Les mêmes cartes et les mêmes filtres
 * que « Mes réservations », le membre en plus (décision 2026-11-21-demandes-comme-mes-reservations).
 */
class ReceivedBookingListing extends Listing
{
    use FiltersBookings;

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
     * Les actions dans le menu trois points, comme « Mes réservations ».
     */
    public function actionsMenu(): bool
    {
        return true;
    }

    /**
     * Ses logements du pays courant, supprimés compris.
     *
     * @return Builder<Offer>
     */
    protected function bookedOffers(): Builder
    {
        return Offer::query()->withTrashed()
            ->where('user_id', $this->user?->getAuthIdentifier())
            ->where('country_id', CurrentCountry::require()->getKey());
    }

    public function emptyText(): string
    {
        return __('offer::booking.list.received_empty');
    }

    public function searchPlaceholder(): string
    {
        return __('offer::booking.list.search');
    }

    /**
     * @return Builder<Booking>
     */
    public function query(): Builder
    {
        return Booking::query()
            // La note et le nombre d'avis du logement, comme dans « Mes réservations ».
            ->with(['offer' => fn ($offer) => $offer->with(['media', 'address'])->withReviewSummary(), 'user'])
            // Sa dernière notification du fil de la demande : la carte ouvre sa page, comme dans « Mes réservations ».
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
        // La carte de « Mes réservations », le membre en tête du sous-titre ; elle ouvre la page de la demande,
        // où se lit son message ; sans notification dans son fil, la fiche du logement ; le statut sur la photo
        // (décision 2026-11-21-demandes-comme-mes-reservations).
        $notification = $record->getAttribute('thread_notification_id');
        $item = BookingItem::make($record, $locale, withGuest: true, mine: true)
            ->link(is_string($notification) && $notification !== '' ? '/notifications/'.$notification : '/offers/'.$record->offer_id)
            ->tag($record->status->label(), $record->status->tone(), onImage: true);

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
