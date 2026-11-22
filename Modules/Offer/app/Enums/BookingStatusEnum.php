<?php

namespace Modules\Offer\Enums;

/**
 * Où en est une demande (décision 2026-11-19-contrat-reservations) : en attente de
 * la réponse du propriétaire, acceptée, refusée, ou annulée par le membre ; terminée, une fois acceptée et son
 * dernier jour passé, posé chaque nuit par `booking:complete` (décision 2026-11-22-statut-termine-enregistre). Rien
 * n'est supprimé.
 */
enum BookingStatusEnum: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REFUSED = 'refused';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return __('offer::booking.statuses.'.$this->value);
    }

    /**
     * Ton de la pastille dans l'app (décision 2026-11-19-contrat-reservations-logements-profil) :
     * en attente orange, acceptée verte, refusée et annulée grises.
     */
    public function tone(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACCEPTED => 'success',
            self::REFUSED, self::CANCELLED, self::COMPLETED => 'neutral',
        };
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
            self::CANCELLED, self::COMPLETED => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])->all();
    }

    /**
     * Le logement est (ou a été) pris : acceptée, ou terminée (décision 2026-11-22-statut-termine-enregistre).
     *
     * @return list<self>
     */
    public static function held(): array
    {
        return [self::ACCEPTED, self::COMPLETED];
    }
}
