<?php

namespace Modules\Offer\Enums;

use Modules\Offer\Models\Offer;

/**
 * Le type de location d'un logement (décision 2026-11-20-contrat-type-de-location) :
 * il remplace le champ « Meublé » et décide ce qu'on demande (un séjour pour une
 * location meublée, une visite sinon), l'unité du prix et les charges.
 */
enum RentalTypeEnum: string
{
    case LONG_TERM = 'long_term';
    case FURNISHED = 'furnished';
    case SHARED = 'shared';

    public function label(): string
    {
        return __('offer::rental.rental_types.'.$this->value);
    }

    /** Une location meublée : un séjour, au jour, charges comprises. */
    public function isFurnished(): bool
    {
        return $this === self::FURNISHED;
    }

    /** Le type de location d'une offre (`details.rental_type`), ou `null`. */
    public static function of(Offer $offer): ?self
    {
        $value = $offer->detail('rental_type');

        return is_string($value) ? self::tryFrom($value) : null;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])->all();
    }

    /**
     * Les valeurs qui ne sont pas meublées (une visite, au mois, avec charges).
     *
     * @return list<string>
     */
    public static function unfurnishedValues(): array
    {
        return [self::LONG_TERM->value, self::SHARED->value];
    }
}
