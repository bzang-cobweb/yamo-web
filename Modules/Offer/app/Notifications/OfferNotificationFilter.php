<?php

namespace Modules\Offer\Notifications;

use App\Support\ContainsSearch;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Notification\Contracts\NotificationFilter;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Enums\RentalTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Support\BookingCache;

/**
 * Les filtres des notifications liées à une offre (décision
 * 2026-11-20-filtres-notifications) :
 * - un type, « Demande de logement » : les notifications d'une demande, une par action (`BookingNotification`) ;
 * - pour ce type : le statut réel de la demande, le logement, le type de location ;
 * - la recherche, dans le titre du logement.
 *
 * Une notification sans offre ne passe pas le filtre « Logement ».
 */
class OfferNotificationFilter implements NotificationFilter
{
    /** La clé du groupe « Demande de logement » dans le filtre « Type » : toutes les actions. */
    public const BOOKING = 'booking';

    /**
     * Le filtre « Type » (décision 2026-11-22-filtre-type-par-action) : le groupe « Demande de logement », toutes
     * actions, puis une option par action, du nom de sa carte (« Nouvelle demande », « Demande modifiée », « Demande
     * acceptée », « Demande refusée », « Demande annulée »), chacune sa classe de notification.
     */
    public function types(): array
    {
        // Sans domaine choisi, « Demande » (le libellé par défaut d'un domaine).
        $label = app(DomainRegistry::class)->active()?->requestLabel() ?? __('offer::booking.notify.title');
        $types = [self::BOOKING => ['label' => $label, 'types' => BookingStepEnum::notifications()]];
        foreach ([BookingStepEnum::RECEIVED, BookingStepEnum::UPDATED, BookingStepEnum::ACCEPTED, BookingStepEnum::REFUSED, BookingStepEnum::CANCELLED] as $step) {
            $types[self::action($step)] = ['label' => BookingNotification::title($step, false), 'types' => [$step->notification()]];
        }

        return $types;
    }

    /** La clé d'une action dans le filtre « Type » (`booking_accepted`). */
    public static function action(BookingStepEnum $step): string
    {
        return self::BOOKING.'_'.$step->value;
    }

    /**
     * Les valeurs du filtre « Type » qui montrent les filtres d'une demande : le groupe et chaque action.
     *
     * @return list<string>
     */
    private static function bookingTypes(): array
    {
        return [self::BOOKING, ...array_map(fn (BookingStepEnum $step): string => self::action($step), BookingStepEnum::cases())];
    }

    /**
     * La pastille d'une notification de demande : le statut **réel** de la demande,
     * lu à l'affichage (décision 2026-11-20-statut-reel) ; à défaut (demande supprimée,
     * ancienne notification sans `booking_id`), celui que nomme l'étape notifiée.
     */
    /** Les demandes de ces notifications, lues en une fois (décision 2026-11-20-cache-fil). */
    public function prepare(array $notifications): void
    {
        $ids = [];
        foreach ($notifications as $notification) {
            $id = ((array) $notification->data)['booking_id'] ?? null;
            if (BookingStepEnum::fromNotification($notification->getAttribute('type')) !== null && is_int($id)) {
                $ids[] = $id;
            }
        }

        app(BookingCache::class)->load($ids);
    }

    /**
     * Le type de l'action, sur la carte (« Nouvelle demande », « Demande acceptée »), dans la langue du lecteur
     * (décision 2026-11-22-notification-par-action) ; `null` pour une autre notification.
     */
    public function cardTitle(DatabaseNotification $notification): ?string
    {
        $step = BookingStepEnum::fromNotification($notification->getAttribute('type'));

        return $step === null ? null : BookingNotification::title($step, (bool) (((array) $notification->data)['own'] ?? false));
    }

    public function tag(DatabaseNotification $notification): ?array
    {
        $step = BookingStepEnum::fromNotification($notification->getAttribute('type'));
        if ($step === null) {
            return null;
        }

        $id = ((array) $notification->data)['booking_id'] ?? null;
        $status = (is_int($id) ? app(BookingCache::class)->find($id)?->status : null) ?? $step->status();

        return self::statusTag($status);
    }

    /** L'étape d'une demande dans son suivi (« Demande acceptée »), dans la langue du lecteur. */
    public function step(DatabaseNotification $notification): ?string
    {
        $step = BookingStepEnum::fromNotification($notification->getAttribute('type'));

        return $step === null ? null : __('offer::booking.history.'.$step->value);
    }

    /** La pastille d'un statut de demande : son libellé et sa couleur. Aussi dans le calendrier. */
    public static function statusTag(BookingStatusEnum $status): array
    {
        return ['label' => $status->label(), 'tone' => $status->tone()];
    }

    public function fields(User $user): array
    {
        return [
            // Pour une demande de logement : son statut réel, le logement, son type de location.
            Field::select('status')
                ->label(__('offer::booking.profile.filter_status'))
                ->options(BookingStatusEnum::options())
                ->visibleWhen('type', self::bookingTypes()),
            Field::select('offer')
                ->label(__('offer::booking.profile.filter_offer'))
                ->options($this->offers($user))
                ->searchable()
                ->visibleWhen('type', self::bookingTypes()),
            Field::select('rental_type')
                ->label(__('offer::booking.notify.filter_rental_type'))
                ->options(RentalTypeEnum::options())
                ->visibleWhen('type', self::bookingTypes()),
        ];
    }

    public function apply(Builder $query, array $filters, User $user): void
    {
        // Le statut réel de la demande (décision 2026-11-20-statut-reel).
        $status = $filters['status'] ?? null;
        if (is_string($status) && BookingStatusEnum::tryFrom($status) !== null) {
            $ids = Booking::query()->whereKey($this->bookingIds($user))->where('status', $status)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
            $query->whereIn('data->booking_id', $ids === [] ? [0] : $ids);
        }

        $rentalType = $filters['rental_type'] ?? null;
        if (is_string($rentalType) && RentalTypeEnum::tryFrom($rentalType) !== null) {
            $ids = Offer::query()->withTrashed()->whereKey($this->offerIds($user))->where('details->rental_type', $rentalType)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
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
     * Les demandes que nomment ses notifications.
     *
     * @return list<int>
     */
    private function bookingIds(User $user): array
    {
        return $user->notifications()->get()
            ->map(fn ($notification): int => (int) (((array) $notification->data)['booking_id'] ?? 0))
            ->filter()
            ->unique()
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
