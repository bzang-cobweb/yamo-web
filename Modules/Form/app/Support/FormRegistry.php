<?php

namespace Modules\Form\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

/**
 * Les formulaires que l'API sait décrire, par nom. Chaque module inscrit les
 * siens dans son fournisseur de services.
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

        // Indisponible (ex. module sans domaine choisi) : comme inconnu.
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
