<?php

namespace Modules\Offer\Notifications;

use App\Support\ContainsSearch;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Notification\Contracts\NotificationFilter;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Models\Offer;

/**
 * Les filtres des notifications liées à une offre (décision
 * 2026-11-20-filtres-notifications) :
 * - le logement, parmi ceux que nomment ses notifications ;
 * - le statut du logement (publié, brouillon, archivé) ;
 * - la recherche, dans le titre du logement.
 *
 * Une notification sans offre ne passe aucun de ces filtres.
 */
class OfferNotificationFilter implements NotificationFilter
{
    public function fields(User $user): array
    {
        return [
            Field::select('offer')
                ->label(__('offer::booking.profile.filter_offer'))
                ->options($this->offers($user)),
            Field::select('offer_status')
                ->label(__('offer::booking.notify.filter_offer_status'))
                ->options(OfferStatusEnum::options()),
        ];
    }

    public function apply(Builder $query, array $filters, User $user): void
    {
        $offer = $filters['offer'] ?? null;
        if (is_scalar($offer) && ctype_digit((string) $offer)) {
            $query->where('data->offer_id', (int) $offer);
        }

        $status = $filters['offer_status'] ?? null;
        if (is_string($status) && OfferStatusEnum::tryFrom($status) !== null) {
            $ids = Offer::query()->withTrashed()->where('status', $status)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
            $query->whereIn('data->offer_id', $ids === [] ? [0] : $ids);
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
