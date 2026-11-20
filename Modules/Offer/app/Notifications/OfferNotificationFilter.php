<?php

namespace Modules\Offer\Notifications;

use App\Support\ContainsSearch;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Notification\Contracts\NotificationFilter;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;

/**
 * Les filtres des notifications liées à une offre (décision
 * 2026-11-20-filtres-notifications) :
 * - les types de ses notifications : les quatre étapes d'une demande ;
 * - le logement, parmi ceux que nomment ses notifications, affiché pour un type de demande ;
 * - le type de logement : la catégorie de l'offre (« Location meublée »), affiché pour un type de demande ;
 * - la recherche, dans le titre du logement.
 *
 * Une notification sans offre ne passe pas le filtre « Logement ».
 */
class OfferNotificationFilter implements NotificationFilter
{
    /**
     * Les quatre étapes d'une demande, nommées par le domaine (« Demande de logement
     * acceptée »).
     */
    public function types(): array
    {
        // Sans domaine choisi, « Demande » (le libellé par défaut d'un domaine).
        $request = app(DomainRegistry::class)->active()?->requestLabel() ?? __('offer::booking.notify.title');

        return [
            BookingRequestReceived::class => __('offer::booking.notify.requested.type', ['request' => $request]),
            BookingRequestAccepted::class => __('offer::booking.notify.accepted.type', ['request' => $request]),
            BookingRequestRefused::class => __('offer::booking.notify.refused.type', ['request' => $request]),
            BookingRequestCancelled::class => __('offer::booking.notify.cancelled.type', ['request' => $request]),
        ];
    }

    /**
     * La pastille d'une étape de demande, selon son type : son libellé (« Demande de
     * logement acceptée ») et la couleur de son statut (en attente orange, acceptée
     * verte, refusée et annulée grises).
     */
    public function tag(string $type): ?array
    {
        $status = match ($type) {
            BookingRequestReceived::class => BookingStatusEnum::PENDING,
            BookingRequestAccepted::class => BookingStatusEnum::ACCEPTED,
            BookingRequestRefused::class => BookingStatusEnum::REFUSED,
            BookingRequestCancelled::class => BookingStatusEnum::CANCELLED,
            default => null,
        };

        // Le libellé du type (« Demande de logement acceptée »), la couleur du statut.
        return $status === null ? null : ['label' => $this->types()[$type], 'tone' => $status->tone()];
    }

    public function fields(User $user): array
    {
        return [
            // Le logement, seulement pour un type de demande.
            Field::select('offer')
                ->label(__('offer::booking.profile.filter_offer'))
                ->options($this->offers($user))
                ->searchable()
                ->visibleWhen('type', array_keys($this->types())),
            // Le type de logement : la catégorie de l'offre (« Location meublée »), pour un type de demande.
            Field::select('category')
                ->label(__('offer::booking.notify.filter_booking_type'))
                ->options($this->categories($user))
                ->visibleWhen('type', array_keys($this->types())),
        ];
    }

    public function apply(Builder $query, array $filters, User $user): void
    {
        $category = $filters['category'] ?? null;
        if (is_scalar($category) && ctype_digit((string) $category)) {
            $ids = Offer::query()->withTrashed()->whereKey($this->offerIds($user))->where('category_id', (int) $category)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
            $query->whereIn('data->offer_id', $ids === [] ? [0] : $ids);
        }

        $offer = $filters['offer'] ?? null;
        if (is_scalar($offer) && ctype_digit((string) $offer)) {
            $query->where('data->offer_id', (int) $offer);
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
     * Les catégories des offres que nomment ses notifications (« Location meublée »), dans l'ordre des catégories.
     *
     * @return array<string, string>
     */
    private function categories(User $user): array
    {
        $ids = Offer::query()->withTrashed()->whereKey($this->offerIds($user))->whereNotNull('category_id')->distinct()->pluck('category_id')->all();

        return OfferCategory::query()->whereKey($ids)->orderBy('position')->orderBy('id')->get()
            ->mapWithKeys(fn (OfferCategory $category): array => [(string) $category->getKey() => (string) $category->getTranslation('name', app()->getLocale())])
            ->all();
    }

    /**
     * Les offres que nomment ses notifications.
     *
     * @return list<int>
     */
    private function offerIds(User $user): array
    {
        return $user->notifications()->get()
            ->map(fn ($notification): int => (int) (((array) $notification->data)['offer_id'] ?? 0))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Les offres que nomment ses notifications, par titre.
     *
     * @return array<string, string>
     */
    private function offers(User $user): array
    {
        $ids = $this->offerIds($user);

        return Offer::query()->withTrashed()->whereKey($ids)->get()
            ->sortBy(fn (Offer $offer): string => (string) $offer->getTranslation('title', app()->getLocale()))
            ->mapWithKeys(fn (Offer $offer): array => [(string) $offer->getKey() => (string) $offer->getTranslation('title', app()->getLocale())])
            ->all();
    }
}
