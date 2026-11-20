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
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Models\Offer;

/**
 * Les filtres des notifications liées à une offre (décision
 * 2026-11-20-filtres-notifications) :
 * - les types de ses notifications : les quatre étapes d'une demande ;
 * - le logement, parmi ceux que nomment ses notifications, affiché pour un type de demande ;
 * - le type de logement (location meublée : séjour ; longue durée : visite), affiché pour un type de demande ;
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
            // Le type de logement (location meublée : séjour ; longue durée : visite), pour un type de demande.
            Field::select('booking_type')
                ->label(__('offer::booking.notify.filter_booking_type'))
                ->options(app(DomainRegistry::class)->active()?->bookingTypeLabels() ?? [])
                ->visibleWhen('type', array_keys($this->types())),
        ];
    }

    public function apply(Builder $query, array $filters, User $user): void
    {
        $bookingType = $filters['booking_type'] ?? null;
        if (is_string($bookingType) && BookingTypeEnum::tryFrom($bookingType) !== null) {
            $query->whereIn('data->offer_id', $this->offersOfType($user, BookingTypeEnum::from($bookingType)) ?: [0]);
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
     * Ses offres nommées dans ses notifications qui se demandent ainsi (séjour ou visite),
     * selon le domaine (un meublé se réserve, un non meublé se visite).
     *
     * @return list<int>
     */
    private function offersOfType(User $user, BookingTypeEnum $type): array
    {
        $domain = app(DomainRegistry::class)->active();
        if ($domain === null) {
            return [];
        }

        return Offer::query()->withTrashed()->whereKey($this->offerIds($user))->get()
            ->filter(fn (Offer $offer): bool => $domain->bookingType($offer) === $type)
            ->map(fn (Offer $offer): int => (int) $offer->getKey())
            ->values()
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
