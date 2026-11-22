<?php

namespace Modules\Offer\Enums;

use Modules\Offer\Notifications\BookingNotification;
use Modules\Offer\Notifications\BookingRequestAccepted;
use Modules\Offer\Notifications\BookingRequestCancelled;
use Modules\Offer\Notifications\BookingRequestReceived;
use Modules\Offer\Notifications\BookingRequestRefused;
use Modules\Offer\Notifications\BookingRequestUpdated;

/**
 * L'étape d'une demande, une classe de notification par étape (décision
 * 2026-11-22-notification-par-action) : reçue par le propriétaire, acceptée ou
 * refusée pour le membre, annulée par le membre pour le propriétaire ; modifiée par le membre
 * tant qu'elle est en attente (décision 2026-11-21-demande-modifiee-notification).
 * Enregistrée dans `data.step` de la notification.
 */
enum BookingStepEnum: string
{
    case RECEIVED = 'received';
    case ACCEPTED = 'accepted';
    case REFUSED = 'refused';
    case CANCELLED = 'cancelled';
    case UPDATED = 'updated';

    /** L'étape d'une réponse du propriétaire, selon le statut donné. */
    public static function answer(BookingStatusEnum $status): self
    {
        return $status === BookingStatusEnum::ACCEPTED ? self::ACCEPTED : self::REFUSED;
    }

    /**
     * La classe de notification de l'étape (décision 2026-11-22-notification-par-action).
     *
     * @return class-string<BookingNotification>
     */
    public function notification(): string
    {
        return match ($this) {
            self::RECEIVED => BookingRequestReceived::class,
            self::UPDATED => BookingRequestUpdated::class,
            self::ACCEPTED => BookingRequestAccepted::class,
            self::REFUSED => BookingRequestRefused::class,
            self::CANCELLED => BookingRequestCancelled::class,
        };
    }

    /**
     * Les classes de notification d'une demande, toutes étapes.
     *
     * @return list<class-string<BookingNotification>>
     */
    public static function notifications(): array
    {
        return array_map(fn (self $step): string => $step->notification(), self::cases());
    }

    /** L'étape d'une classe de notification ; `null` pour une autre. */
    public static function fromNotification(mixed $type): ?self
    {
        foreach (self::cases() as $step) {
            if ($step->notification() === $type) {
                return $step;
            }
        }

        return null;
    }

    /** La clé des textes `offer::booking.notify.<clé>`. */
    public function textKey(): string
    {
        return $this === self::RECEIVED ? 'requested' : $this->value;
    }

    /** La liste où retrouver la demande : `received` (le propriétaire) ou `mine` (le membre). */
    public function section(): string
    {
        return in_array($this, [self::RECEIVED, self::CANCELLED, self::UPDATED], true) ? 'received' : 'mine';
    }

    /** Le statut de la demande que nomme l'étape : la pastille quand la demande n'existe plus. */
    public function status(): BookingStatusEnum
    {
        return match ($this) {
            self::RECEIVED, self::UPDATED => BookingStatusEnum::PENDING,
            self::ACCEPTED => BookingStatusEnum::ACCEPTED,
            self::REFUSED => BookingStatusEnum::REFUSED,
            self::CANCELLED => BookingStatusEnum::CANCELLED,
        };
    }
}
