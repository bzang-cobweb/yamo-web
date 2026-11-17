<?php

namespace Modules\Location\Filament\Forms;

use Filament\Forms\Components\Select;
use Modules\Location\Models\Country;

/**
 * Liste de pays du back-office : s'il n'y a qu'un pays actif, il est choisi
 * d'office et la liste est verrouillée, comme dans l'app
 * (décision 2026-11-20-pays-unique-identifiant-double).
 */
final class SingleCountry
{
    public static function lock(Select $select): Select
    {
        return $select
            ->disabled(fn (): bool => self::soleActiveId() !== null)
            // Verrouillée, la valeur est quand même enregistrée.
            ->dehydrated()
            ->afterStateHydrated(function (Select $component, mixed $state): void {
                $sole = self::soleActiveId();

                if (blank($state) && $sole !== null) {
                    $component->state($sole);
                }
            });
    }

    /**
     * Identifiant du seul pays actif, ou `null` s'il y en a zéro ou plusieurs.
     */
    public static function soleActiveId(): ?int
    {
        $ids = Country::query()->active()->limit(2)->pluck('id');

        return $ids->count() === 1 ? (int) $ids->first() : null;
    }
}
