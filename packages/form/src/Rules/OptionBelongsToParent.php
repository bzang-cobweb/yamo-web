<?php

namespace Bza\Form\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Dependent list: the chosen option must belong to the parent field's value
 * (e.g. a region of the chosen country).
 */
final class OptionBelongsToParent implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param  array<string, string|null>  $parents  option value => parent value
     */
    public function __construct(
        private readonly string $parentField,
        private readonly array $parents,
    ) {}

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // One parent or several (chips); one option or several: each must belong to one of the chosen parents
        // (the city in one of the chosen regions, decision 2026-11-26-region-ville-en-puces).
        $parents = array_map('strval', (array) data_get($this->data, $this->parentField));

        foreach ((array) $value as $option) {
            if (! in_array($this->parents[(string) $option] ?? null, $parents, true)) {
                $fail('form::validation.option_parent')->translate();

                return;
            }
        }
    }
}
