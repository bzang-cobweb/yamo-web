<?php

namespace Modules\Offer\Lists;

use Illuminate\Database\Eloquent\Builder;
use Modules\Form\Support\Field;
use Modules\Offer\Enums\BookingStatusEnum;
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
     * La période d'une demande, d'après ses dates seulement : à venir, en cours, passées ; vide :
     * toutes (décision 2026-11-21-reservations-periode-dates).
     */
    public static function period(string $name = 'period', bool $chips = false): Field
    {
        // En puces, plusieurs périodes à la fois (« Mes réservations », décision 2026-11-26-reservations-filtres-en-puces).
        return ($chips ? Field::chips($name) : Field::select($name))
            ->label(__('offer::booking.profile.filter_period'))
            ->options(collect(Booking::PERIODS)->mapWithKeys(fn (string $period): array => [$period => __('offer::booking.profile.period_'.$period)])->all());
    }

    /**
     * @param  Builder<Booking>  $query
     * @param  array<string, mixed>  $filters
     */
    public static function applyPeriod(Builder $query, array $filters, string $name = 'period'): void
    {
        // Une période ou plusieurs (puces) : les demandes de l'une d'elles ; une valeur inconnue est ignorée.
        $periods = array_values(array_intersect(array_map('strval', (array) ($filters[$name] ?? [])), Booking::PERIODS));
        if ($periods !== []) {
            $query->where(function (Builder $any) use ($periods): void {
                foreach ($periods as $period) {
                    $any->orWhere(fn (Builder $one) => $one->inPeriod($period));
                }
            });
        }
    }

    /**
     * Le filtre du statut réel d'une liste de demandes, en puces (décision 2026-11-27-statuts-en-puces).
     */
    public static function statuses(string $name = 'status'): Field
    {
        return Field::chips($name)
            ->label(__('offer::booking.profile.filter_status'))
            ->options(BookingStatusEnum::options());
    }

    /**
     * Les demandes de l'un des statuts choisis (une valeur ou plusieurs) ; une valeur inconnue est ignorée. « En
     * attente » : sans les expirées (décision 2026-11-22-statuts-terminee-expiree).
     *
     * @param  Builder<Booking>  $query
     * @param  array<string, mixed>  $filters
     */
    public static function applyStatuses(Builder $query, array $filters, string $name = 'status'): void
    {
        $statuses = array_values(array_filter(array_map(fn (mixed $status): ?BookingStatusEnum => BookingStatusEnum::tryFrom((string) $status), (array) ($filters[$name] ?? []))));
        if ($statuses === []) {
            return;
        }

        $query->where(function (Builder $any) use ($statuses): void {
            foreach ($statuses as $status) {
                $status === BookingStatusEnum::PENDING
                    ? $any->orWhere(fn (Builder $one) => $one->awaitingAnswer())
                    : $any->orWhere('status', $status);
            }
        });
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
