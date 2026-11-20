<?php

namespace Modules\Offer\Notifications;

use App\Support\ContainsSearch;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Notification\Contracts\NotificationFilter;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Models\Offer;

/**
 * Les filtres des notifications liées à une offre (décision
 * 2026-11-20-filtres-notifications) :
 * - le logement, parmi ceux que nomment ses notifications ;
 * - le statut de la demande, celui de la pastille (en attente, acceptée, refusée, annulée) ;
 * - la recherche, dans le titre du logement.
 *
 * Une notification sans offre, ou sans demande, ne passe pas le filtre correspondant.
 */
class OfferNotificationFilter implements NotificationFilter
{
    public function fields(User $user): array
    {
        return [
            Field::select('offer')
                ->label(__('offer::booking.profile.filter_offer'))
                ->options($this->offers($user))
                ->searchable(),
            // Le statut de la demande, tel que la pastille de la notification le montre (à l'envoi).
            Field::select('status')
                ->label(__('offer::booking.profile.filter_status'))
                ->options(BookingStatusEnum::options()),
        ];
    }

    public function apply(Builder $query, array $filters, User $user): void
    {
        $offer = $filters['offer'] ?? null;
        if (is_scalar($offer) && ctype_digit((string) $offer)) {
            $query->where('data->offer_id', (int) $offer);
        }

        $status = $filters['status'] ?? null;
        if (is_string($status) && BookingStatusEnum::tryFrom($status) !== null) {
            $query->where('data->booking_status', $status);
        }
    }

    public function orSearch(Builder $query, string $term): void
    {
        $ids = Offer::query()->withTrashed()
            ->where(fn (Builder $titles) => ContainsSearch::orWhere($titles, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($ids !== []) {
            $query->orWhereIn('data->offer_id', $ids);
        }
    }

    /**
     * Les offres que nomment ses notifications, par titre.
     *
     * @return array<string, string>
     */
    private function offers(User $user): array
    {
        $ids = $user->notifications()->get()
            ->map(fn ($notification): int => (int) (((array) $notification->data)['offer_id'] ?? 0))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Offer::query()->withTrashed()->whereKey($ids)->get()
            ->sortBy(fn (Offer $offer): string => (string) $offer->getTranslation('title', app()->getLocale()))
            ->mapWithKeys(fn (Offer $offer): array => [(string) $offer->getKey() => (string) $offer->getTranslation('title', app()->getLocale())])
            ->all();
    }
}
