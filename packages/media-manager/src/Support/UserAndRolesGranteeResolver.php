<?php

namespace Bza\MediaManager\Support;

use Bza\MediaManager\Contracts\GranteeResolver;
use Bza\MediaManager\Grantee;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * The user itself, and its roles when it has a "roles" relation
 * (spatie/laravel-permission's HasRoles).
 */
class UserAndRolesGranteeResolver implements GranteeResolver
{
    public function grantees(Authenticatable $user): array
    {
        if (! $user instanceof Model) {
            return [];
        }

        $grantees = [Grantee::of($user)];

        if (method_exists($user, 'roles')) {
            foreach ($user->roles as $role) {
                $grantees[] = Grantee::of($role);
            }
        }

        return $grantees;
    }
}
