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

/**
 * Les filtres des notifications liées à une offre (décision
 * 2026-11-20-filtres-notifications) :
 * - un type, « Demande de logement » : la notification `BookingRequested`, toutes étapes ;
 * - pour ce type : le statut réel de la demande, le logement, le type de location ;
 * - la recherche, dans le titre du logement.
 *
 * Une notification sans offre ne passe pas le filtre « Logement ».
 */
class OfferNotificationFilter implements NotificationFilter
{
    /** La clé du groupe « Demande de logement » dans le filtre « Type ». */
    public const BOOKING = 'booking';

    /** @var array<int, BookingStatusEnum|null> Statuts déjà lus, par demande. */
    private array $statuses = [];

    public function types(): array
    {
        // Sans domaine choisi, « Demande » (le libellé par défaut d'un domaine).
        $label = app(DomainRegistry::class)->active()?->requestLabel() ?? __('offer::booking.notify.title');

        return [self::BOOKING => ['label' => $label, 'type' => BookingRequested::class]];
    }

    /**
     * La pastille d'une notification de demande : le statut **réel** de la demande,
     * lu à l'affichage (décision 2026-11-20-statut-reel) ; à défaut (demande supprimée,
     * ancienne notification sans `booking_id`), celui que nomme l'étape notifiée.
     */
    public function tag(DatabaseNotification $notification): ?array
    {
        if ($notification->getAttribute('type') !== BookingRequested::class) {
            return null;
        }

        $data = (array) $notification->data;
        $id = $data['booking_id'] ?? null;
        $step = BookingStepEnum::tryFrom(is_string($data['step'] ?? null) ? $data['step'] : '') ?? BookingStepEnum::RECEIVED;
        $status = (is_int($id) ? $this->status($id) : null) ?? $step->status();

        return self::statusTag($status);
    }

    /** L'étape d'une demande dans son suivi (« Demande acceptée »), dans la langue du lecteur. */
    /** Le propriétaire à droite (sa réponse), le membre à gauche (sa demande, son annulation). */
    public function side(DatabaseNotification $notification): ?string
    {
        if ($notification->getAttribute('type') !== BookingRequested::class) {
            return null;
        }

        $step = ((array) $notification->data)['step'] ?? null;

        return (BookingStepEnum::tryFrom(is_string($step) ? $step : '') ?? BookingStepEnum::RECEIVED)->section() === 'mine' ? 'end' : 'start';
    }

    public function step(DatabaseNotification $notification): ?string
    {
        if ($notification->getAttribute('type') !== BookingRequested::class) {
            return null;
        }

        $step = ((array) $notification->data)['step'] ?? null;

        return __('offer::booking.history.'.(BookingStepEnum::tryFrom(is_string($step) ? $step : '') ?? BookingStepEnum::RECEIVED)->value);
    }

    /** La pastille d'un statut de demande : son libellé et sa couleur. Aussi dans le calendrier. */
    public static function statusTag(BookingStatusEnum $status): array
    {
        return ['label' => $status->label(), 'tone' => $status->tone()];
    }

    private function status(int $booking): ?BookingStatusEnum
    {
        if (! array_key_exists($booking, $this->statuses)) {
            $this->statuses[$booking] = Booking::query()->whereKey($booking)->value('status');
            $value = $this->statuses[$booking];
            $this->statuses[$booking] = $value instanceof BookingStatusEnum ? $value : (is_string($value) ? BookingStatusEnum::tryFrom($value) : null);
        }

        return $this->statuses[$booking];
    }

    public function fields(User $user): array
    {
        return [
            // Pour une demande de logement : son statut réel, le logement, son type de location.
            Field::select('status')
                ->label(__('offer::booking.profile.filter_status'))
                ->options(BookingStatusEnum::options())
                ->visibleWhen('type', self::BOOKING),
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
