<?php

namespace Modules\Offer\Lists;

use Illuminate\Database\Eloquent\Builder;
use Modules\Form\Support\Field;
use Modules\Offer\Models\Booking;

/**
 * Le filtre de statut des listes de demandes (décision
 * 2026-11-19-contrat-reservations-logements-profil) : à venir, en attente,
 * passées ; « Tous » par défaut. Le même pour le voyageur et pour l'hôte.
 */
final class BookingFilters
{
    /**
     * Le champ du filtre ; `$name` et `$label` le distinguent d'un autre statut de la
     * même liste (« Mes logements » a déjà le statut de l'annonce).
     */
    public static function status(string $name = 'status', ?string $label = null): Field
    {
        return Field::segmented($name)
            ->label($label ?? __('offer::booking.profile.filter_status'))
            ->options([
                'any' => __('offer::booking.profile.filter_any'),
                ...collect(Booking::STATUS_GROUPS)->mapWithKeys(fn (string $group): array => [$group => __('offer::booking.profile.filter_'.$group)])->all(),
            ])
            ->default('any');
    }

    /**
     * Le même filtre en liste déroulante, sans « Tous » (vide : toutes) : pour une liste
     * où il côtoie d'autres listes déroulantes (« Mes logements »).
     */
    public static function statusSelect(string $name, string $label): Field
    {
        return Field::select($name)
            ->label($label)
            ->options(collect(Booking::STATUS_GROUPS)->mapWithKeys(fn (string $group): array => [$group => __('offer::booking.profile.filter_'.$group)])->all());
    }

    /**
     * @param  Builder<Booking>  $query
     * @param  array<string, mixed>  $filters
     */
    public static function applyStatus(Builder $query, array $filters, string $name = 'status'): void
    {
        $group = self::group($filters, $name);
        if ($group !== null) {
            $query->inStatusGroup($group);
        }
    }

    /**
     * Le regroupement choisi (`upcoming`, `pending`, `past`), ou `null` pour « Tous ».
     *
     * @param  array<string, mixed>  $filters
     */
    public static function group(array $filters, string $name = 'status'): ?string
    {
        $group = $filters[$name] ?? 'any';

        return is_string($group) && in_array($group, Booking::STATUS_GROUPS, true) ? $group : null;
    }
}
