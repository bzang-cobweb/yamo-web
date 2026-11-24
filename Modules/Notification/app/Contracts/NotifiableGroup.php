<?php

namespace Modules\Notification\Contracts;

use Modules\Auth\Models\User;

/**
 * Un groupe de comptes qui partagent un fil de notifications (décision 2026-11-24-notifications-fil-de-groupe) : un
 * partenaire et ses comptes. Toutes les notifications du fil d'un groupe (`partner:group:5`) se lisent par chacun de
 * ses membres, quel que soit leur destinataire. Le modèle l'implémente, de préférence avec `HasNotifiableGroup`, et le
 * module l'inscrit auprès de `NotificationThreads::registerGroup()`.
 */
interface NotifiableGroup
{
    /** Le préfixe du fil de ses groupes (`partner:group:`). */
    public static function groupThreadPrefix(): string;

    /** Le fil de ce groupe (`partner:group:5`). */
    public function groupThread(): string;

    /**
     * Les fils des groupes dont le compte est membre.
     *
     * @return list<string>
     */
    public static function groupThreadsOf(User $user): array;
}
