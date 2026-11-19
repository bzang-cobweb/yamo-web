<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Forms\BookingAnswerForm;
use Modules\Offer\Models\Booking;

/**
 * « Réservations reçues » (décision 2026-11-19-contrat-reservations) : les demandes
 * sur les offres du compte dans le pays courant, la plus récente en tête ; le
 * membre, l'offre, les dates, le statut ; « Répondre » tant qu'elle attend.
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
        return __('offer::booking.list.received');
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
            ->with(['offer.media', 'offer.address', 'user'])
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
        $item = BookingItem::make($record, $locale, withGuest: true)->link('/offers/'.$record->offer_id);

        return $record->isAwaitingAnswer() ? $item->edit(BookingAnswerForm::name()) : $item;
    }
}
