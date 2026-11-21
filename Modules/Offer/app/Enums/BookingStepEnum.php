<?php

namespace Modules\Offer\Enums;

/**
 * L'étape d'une demande que notifie `BookingRequested` (décision
 * 2026-11-20-notification-demande-unique) : reçue par le propriétaire, acceptée ou
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
