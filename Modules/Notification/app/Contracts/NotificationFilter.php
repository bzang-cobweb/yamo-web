<?php

namespace Modules\Notification\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Form\Support\Field;

/**
 * Des filtres de la liste des notifications, fournis par un autre module (décision
 * 2026-11-20-filtres-notifications) : le module Offer filtre par logement et par
 * statut du logement, et cherche dans le titre du logement. Le module Notification
 * ne connaît pas les offres.
 */
interface NotificationFilter
{
    /**
     * Les champs du filtre, pour le compte `$user`.
     *
     * @return list<Field>
     */
    public function fields(User $user): array;

    /**
     * Restreint `$query` aux valeurs choisies (`filter[nom]`).
     *
     * @param  Builder<DatabaseNotification>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters, User $user): void;

    /**
     * Élargit la recherche (`search`) : ajoute ses propres correspondances, en `orWhere`,
     * au groupe de la recherche (le titre du logement, pour le module Offer).
     *
     * @param  Builder<DatabaseNotification>  $query
     */
    public function orSearch(Builder $query, string $term): void;
}
