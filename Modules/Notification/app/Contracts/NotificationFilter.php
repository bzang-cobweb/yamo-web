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
     * Les types de notification du module, pour le filtre « Type » : clé => son libellé
     * et sa classe de notification, la valeur de la colonne `type` (« booking » =>
     * « Demande de logement », `BookingRequested`).
     *
     * @return array<string, array{label: string, type: class-string}>
     */
    public function types(): array;

    /**
     * La pastille d'une notification du module (« Acceptée », ton `success`), lue à
     * l'affichage ; `null` pour une notification qui n'est pas la sienne.
     *
     * @return array{label: string, tone: string}|null
     */
    public function tag(DatabaseNotification $notification): ?array;

    /**
     * Le libellé d'une étape du fil d'une notification du module (« Demande acceptée »),
     * dans la langue courante ; `null` pour une notification qui n'est pas la sienne
     * (décision 2026-11-20-suivi-demande).
     */
    public function step(DatabaseNotification $notification): ?string;

    /**
     * Le côté d'une étape dans le suivi (décision 2026-11-20-suivi-conversation) :
     * `start` (à gauche) ou `end` (à droite, le propriétaire d'une demande) ; `null`
     * pour une notification qui n'est pas la sienne.
     */
    public function side(DatabaseNotification $notification): ?string;

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
