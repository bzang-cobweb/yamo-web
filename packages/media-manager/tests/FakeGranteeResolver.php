<?php

namespace Bza\MediaManager\Tests;

use Bza\MediaManager\Contracts\GranteeResolver;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Gives each test user the grantees it is built with, without any role table.
 */
class FakeGranteeResolver implements GranteeResolver
{
    public function grantees(Authenticatable $user): array
    {
        return $user instanceof FakeUser ? $user->grantees : [];
    }
}
