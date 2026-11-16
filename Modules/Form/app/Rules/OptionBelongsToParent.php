<?php

namespace Modules\Form\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Liste dépendante : l'option choisie doit appartenir à la valeur du champ parent
 * (ex. une région du pays choisi).
 */
final class OptionBelongsToParent implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param  array<string, string|null>  $parents  valeur de l'option => valeur du parent
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
        $parent = data_get($this->data, $this->parentField);

        if (($this->parents[(string) $value] ?? null) !== (string) $parent) {
            $fail('form::validation.option_parent')->translate();
        }
    }
}
