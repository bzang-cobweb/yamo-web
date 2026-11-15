<?php

namespace Modules\Auth\Policies\Concerns;

use Modules\Auth\Models\User;

trait HasBeforeAbility
{
    /**
     * Le super administrateur a tous les droits ; les autres comptes passent par
     * la méthode de la policy.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }
}
