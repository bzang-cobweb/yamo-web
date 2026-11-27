<?php

namespace Bza\MediaManager\Tests;

use Bza\MediaManager\Grantee;
use Illuminate\Auth\GenericUser;

/**
 * A user holding rights through the given grantees ("role#editor"…).
 */
class FakeUser extends GenericUser
{
    /** @var list<Grantee> */
    public array $grantees;

    public function __construct(string $id, string ...$grantees)
    {
        parent::__construct(['id' => $id]);

        $this->grantees = [new Grantee('user', $id), ...array_map(Grantee::fromKey(...), $grantees)];
    }
}
