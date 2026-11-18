<?php

namespace Modules\Form\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

/**
 * Registre des listes servies à l'app, par nom public ; chaque module y inscrit
 * les siennes, comme ses formulaires (`FormRegistry`).
 */
final class ListingRegistry
{
    /** @var array<string, class-string<Listing>> */
    private array $lists = [];

    /**
     * @param  class-string<Listing>  $list
     */
    public function register(string $list): void
    {
        if (! is_subclass_of($list, Listing::class)) {
            throw new InvalidArgumentException("{$list} n'étend pas ".Listing::class.'.');
        }

        $this->lists[$list::name()] = $list;
    }

    public function has(string $name): bool
    {
        return isset($this->lists[$name]);
    }

    public function make(string $name, ?Authenticatable $user = null): ?Listing
    {
        $class = $this->lists[$name] ?? null;

        // Indisponible (ex. module sans domaine choisi) : comme inconnu.
        return $class === null || ! $class::isAvailable() ? null : new $class($user);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->lists);
    }
}
