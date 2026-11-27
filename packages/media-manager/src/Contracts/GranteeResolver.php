<?php

namespace Bza\MediaManager\Contracts;

use Bza\MediaManager\Grantee;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Lists everything a user holds rights through: usually the user itself
 * and its roles.
 */
interface GranteeResolver
{
    /**
     * @return list<Grantee>
     */
    public function grantees(Authenticatable $user): array;
}
