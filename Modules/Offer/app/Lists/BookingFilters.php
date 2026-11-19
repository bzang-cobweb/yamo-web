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
    public static function status(): Field
    {
        return Field::segmented('status')
            ->label(__('offer::booking.profile.filter_status'))
            ->options([
                'any' => __('offer::booking.profile.filter_any'),
                ...collect(Booking::STATUS_GROUPS)->mapWithKeys(fn (string $group): array => [$group => __('offer::booking.profile.filter_'.$group)])->all(),
            ])
            ->default('any');
    }

    /**
     * @param  Builder<Booking>  $query
     * @param  array<string, mixed>  $filters
     */
    public static function applyStatus(Builder $query, array $filters): void
    {
        $group = $filters['status'] ?? 'any';

        if (is_string($group) && in_array($group, Booking::STATUS_GROUPS, true)) {
            $query->inStatusGroup($group);
        }
    }
}
