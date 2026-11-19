<?php

namespace Modules\Offer\Enums;

/**
 * Ce qu'un membre demande sur une offre (décision 2026-11-19-contrat-reservations) :
 * un séjour, de l'arrivée au départ, ou une visite, à une date souhaitée. Le
 * domaine dit lequel convient à une offre (`Domain::bookingType()`).
 */
enum BookingTypeEnum: string
{
    case STAY = 'stay';
    case VISIT = 'visit';

    public function label(): string
    {
        return __('offer::booking.types.'.$this->value);
    }

    /**
     * Le bouton de la fiche : « Réserver », « Demander une visite ».
     */
    public function actionLabel(): string
    {
        return __('offer::booking.actions.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])->all();
    }
}
