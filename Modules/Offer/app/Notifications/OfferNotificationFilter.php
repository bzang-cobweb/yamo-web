<?php

namespace Modules\Offer\Notifications;

use App\Support\ContainsSearch;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;
use Modules\Notification\Contracts\NotificationFilter;
use Modules\Notification\Support\NotificationThreads;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingStepEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Enums\RentalTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Support\BookingCache;

/**
 * Les filtres des notifications liées à une offre (décision
 * 2026-11-20-filtres-notifications) :
 * - un type, « Réservation » : les notifications d'une demande, une classe par action (`BookingNotification`) ;
 * - pour ce type : le statut réel de la demande (filtre « Statut » commun), le logement, le type de location ;
 * - la recherche, dans le titre du logement.
 *
 * Une notification sans offre ne passe pas le filtre « Logement ».
 */
class OfferNotificationFilter implements NotificationFilter
{
    /** La clé du type « Réservation » dans le filtre « Type » : toutes les actions d'une demande. */
    public const BOOKING = 'booking';

    /**
     * Le filtre « Type » : « Réservation », toutes les actions d'une demande (décisions 2026-11-22-notification-par-action
     * et 2026-11-24-notifications-types-reservation-partenaire) ; le statut se choisit ensuite dans « Statut ».
     */
    public function types(): array
    {
        return [self::BOOKING => ['label' => __('offer::booking.notify.filter_type'), 'types' => BookingStepEnum::notifications()]];
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
    public function cardTitle(DatabaseNotification $notification, User $reader): ?string
    {
        $step = BookingStepEnum::fromNotification($notification->getAttribute('type'));
        if ($step === null) {
            return null;
        }

        // Sa propre action : le lecteur en est l'auteur (décision 2026-11-24-notifications-fil-de-groupe).
        return BookingNotification::title($step, app(NotificationThreads::class)->isOwn($notification, $reader), $this->bookingType($notification));
    }

    /**
     * Le type de la demande d'une notification : celui de la demande, sinon celui enregistré à l'envoi (une demande
     * supprimée) ; `null` pour une ancienne notification d'une demande supprimée (décision
     * 2026-11-26-titres-type-de-demande).
     */
    private function bookingType(DatabaseNotification $notification): ?BookingTypeEnum
    {
        $data = (array) $notification->data;
        $id = $data['booking_id'] ?? null;
        $booking = is_int($id) ? app(BookingCache::class)->find($id) : null;

        return $booking?->type ?? (is_string($data['booking_type'] ?? null) ? BookingTypeEnum::tryFrom($data['booking_type']) : null);
    }

    /**
     * Une notification de demande que le lecteur n'a pas reçue : sa propre action, rédigée pour lui à partir de la
     * demande, dans sa langue (« Vous avez accepté la demande de … », décision 2026-11-24-notifications-fil-de-groupe) ;
     * `null` si la demande n'existe plus.
     */
    public function forReader(DatabaseNotification $notification, User $reader): ?array
    {
        if ($notification->getAttribute('type') === OfferMessageReceived::class) {
            return $this->messageForReader($notification, $reader);
        }

        $step = BookingStepEnum::fromNotification($notification->getAttribute('type'));
        if ($step === null) {
            return null;
        }

        $data = (array) $notification->data;
        $id = $data['booking_id'] ?? null;
        $booking = is_int($id) ? app(BookingCache::class)->find($id) : null;
        if ($booking === null) {
            return null;
        }

        return BookingNotification::for($booking, $step, edited: (bool) ($data['edited'] ?? false))->toArray($reader);
    }

    /**
     * Son propre message, lu par son expéditeur (décision 2026-11-26-messages-editeur) : rédigé dans sa langue, et
     * « Répondre » rouvre le formulaire avec le `record` qui lui convient (l'éditeur répond au membre, le membre écrit à
     * l'éditeur). `null` si l'offre n'existe plus.
     *
     * @return array<string, mixed>|null
     */
    private function messageForReader(DatabaseNotification $notification, User $reader): ?array
    {
        $data = (array) $notification->data;
        $offerId = $data['offer_id'] ?? null;
        $memberId = $data['member_id'] ?? null;
        $offer = is_int($offerId) ? Offer::query()->withTrashed()->find($offerId) : null;
        if (! $offer instanceof Offer || ! is_int($memberId)) {
            return null;
        }

        return OfferMessageReceived::data($offer, $memberId, (string) ($data['message'] ?? ''), (int) $reader->getKey() === (int) $offer->user_id);
    }

    public function tag(DatabaseNotification $notification): ?array
    {
        $step = BookingStepEnum::fromNotification($notification->getAttribute('type'));
        if ($step === null) {
            return null;
        }

        $id = ((array) $notification->data)['booking_id'] ?? null;
        $booking = is_int($id) ? app(BookingCache::class)->find($id) : null;

        // « Terminée » ou « Expirée » d'après les dates (décision 2026-11-22-statuts-terminee-expiree).
        return $booking !== null ? $booking->statusTag() : self::statusTag($step->status());
    }

    /** L'étape d'une demande dans son suivi (« Demande acceptée »), dans la langue du lecteur. */
    public function step(DatabaseNotification $notification, User $reader): ?string
    {
        // Un message (décision 2026-11-26-messages-editeur) : sa bulle montre son texte, à défaut « Message ».
        if ($notification->getAttribute('type') === OfferMessageReceived::class) {
            return __('offer::message.notify.title');
        }

        $step = BookingStepEnum::fromNotification($notification->getAttribute('type'));

        return $step === null ? null : BookingNotification::stepLabel($step, $this->bookingType($notification));
    }

    /** La pastille d'un statut de demande : son libellé et sa couleur. Aussi dans le calendrier. */
    public static function statusTag(BookingStatusEnum $status): array
    {
        return ['label' => $status->label(), 'tone' => $status->tone()];
    }

    /**
     * Le statut réel d'une demande, dans le filtre « Statut » commun, pour les types d'une demande (décisions
     * 2026-11-20-statut-reel et 2026-11-24-notifications-types-groupes-statut).
     */
    public function statuses(): array
    {
        return collect(BookingStatusEnum::options())
            ->map(fn (string $label): array => ['label' => $label, 'parent' => self::BOOKING])
            ->all();
    }

    public function fields(User $user): array
    {
        return [
            // Pour une demande de logement : le logement, son type de location ; son statut réel est dans le filtre
            // « Statut » commun (`statuses()`).
            Field::select('offer')
                ->label(__('offer::booking.profile.filter_offer'))
                ->options($this->offers($user))
                ->searchable()
                ->visibleWhen('type', self::BOOKING),
            Field::select('rental_type')
                ->label(__('offer::booking.notify.filter_rental_type'))
                ->options(RentalTypeEnum::options())
                ->visibleWhen('type', self::BOOKING),
        ];
    }

    public function apply(Builder $query, array $filters, User $user): void
    {
        // Le statut réel de la demande (décision 2026-11-20-statut-reel).
        $status = $filters['status'] ?? null;
        if (is_string($status) && BookingStatusEnum::tryFrom($status) !== null) {
            // « En attente » : sans les expirées (décision 2026-11-22-statuts-terminee-expiree).
            $ids = Booking::query()->whereKey($this->bookingIds($user))
                ->when($status === BookingStatusEnum::PENDING->value, fn (Builder $bookings) => $bookings->awaitingAnswer(), fn (Builder $bookings) => $bookings->where('status', $status))
                ->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
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
        return app(NotificationThreads::class)->visibleTo($user)->get()
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
        return app(NotificationThreads::class)->visibleTo($user)->get()
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
