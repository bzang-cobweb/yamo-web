<?php

namespace Modules\Offer\Enums;

/**
 * Où en est une demande (décision 2026-11-19-contrat-reservations) : en attente de
 * la réponse du propriétaire, acceptée, refusée, ou annulée par le membre. Rien
 * n'est supprimé.
 */
enum BookingStatusEnum: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REFUSED = 'refused';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('offer::booking.statuses.'.$this->value);
    }

    /**
     * Couleur du statut au back-office.
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACCEPTED => 'success',
            self::REFUSED => 'danger',
            self::CANCELLED => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])->all();
    }
}
