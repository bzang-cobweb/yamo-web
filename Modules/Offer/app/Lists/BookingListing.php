<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Form\Support\Submission;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Booking;

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
     * @return Builder<Booking>
     */
    public function query(): Builder
    {
        return Booking::query()
            ->with(['offer.media', 'offer.address'])
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
