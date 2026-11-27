<?php

namespace Bza\Form\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

/**
 * Registry of the lists served to the app, by public name; each module registers
 * its own there, like its forms (`FormRegistry`).
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

        // Unavailable (e.g. module with no chosen domain): treated as unknown.
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
