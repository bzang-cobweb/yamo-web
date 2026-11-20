<?php

namespace Modules\Notification\Contracts;

/**
 * Une notification enregistrée déjà lue (décision 2026-11-20-reponse-dans-son-fil) :
 * celle qui trace, chez qui agit, sa propre action. Elle fait remonter sa carte sans
 * compter comme non lue.
 */
interface SentAsRead
{
    /** Vrai : le canal `database` l'enregistre lue. */
    public function sentAsRead(object $notifiable): bool;
}
