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
     * et ses classes de notification, les valeurs de la colonne `type` (« booking » =>
     * « Demande de logement », une classe par action, décision 2026-11-22-notification-par-action).
     *
     * @return array<string, array{label: string, types: list<class-string>}>
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
     * Le titre de la carte d'une notification du module dans la liste, dans la langue du lecteur
     * (« Demande acceptée » pour une demande, décision 2026-11-22-notification-par-action) ; `null` : son titre.
     */
    public function cardTitle(DatabaseNotification $notification): ?string;

    /**
     * Charge en une fois ce que ces notifications demanderont (une page de la liste) ;
     * `tag()` et les autres n'ont plus à relire, notification par notification (décision
     * 2026-11-20-cache-fil).
     *
     * @param  list<DatabaseNotification>  $notifications
     */
    public function prepare(array $notifications): void;

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
