<?php

namespace Modules\Notification\Concerns;

use Modules\Auth\Models\User;

/**
 * L'implémentation commune de `NotifiableGroup` : le fil d'un groupe est son préfixe suivi de son identifiant. Le
 * modèle ne déclare que son préfixe (`groupThreadPrefix()`) et les identifiants des groupes d'un compte
 * (`groupKeysOf()`).
 */
trait HasNotifiableGroup
{
    /**
     * Les identifiants des groupes dont le compte est membre.
     *
     * @return list<int|string>
     */
    abstract protected static function groupKeysOf(User $user): array;

    public function groupThread(): string
    {
        return static::groupThreadPrefix().$this->getKey();
    }

    /**
     * @return list<string>
     */
    public static function groupThreadsOf(User $user): array
    {
        return array_map(fn (int|string $key): string => static::groupThreadPrefix().$key, static::groupKeysOf($user));
    }
}
