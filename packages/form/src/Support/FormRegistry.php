<?php

namespace Bza\Form\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

/**
 * The forms the API can describe, by name. Each module registers its own
 * in its service provider.
 */
final class FormRegistry
{
    /** @var array<string, class-string<Form>> */
    private array $forms = [];

    /**
     * @param  class-string<Form>  $form
     */
    public function register(string $form): void
    {
        if (! is_subclass_of($form, Form::class)) {
            throw new InvalidArgumentException("{$form} n'étend pas ".Form::class.'.');
        }

        $this->forms[$form::name()] = $form;
    }

    public function has(string $name): bool
    {
        return isset($this->forms[$name]);
    }

    public function make(string $name, ?Authenticatable $user = null): ?Form
    {
        $class = $this->forms[$name] ?? null;

        // Unavailable (e.g. module with no chosen domain): treated as unknown.
        return $class === null || ! $class::isAvailable() ? null : new $class($user);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->forms);
    }
}
