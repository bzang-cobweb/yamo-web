<?php

namespace Modules\Form\Support;

use Illuminate\Validation\Rule;
use Modules\Form\Enums\FieldType;

/**
 * Un champ de formulaire : ce que l'app affiche (libellé, type, options…) et
 * ce que le serveur valide, déclarés au même endroit.
 */
final class Field
{
    private ?string $label = null;

    private ?string $placeholder = null;

    private ?string $help = null;

    private bool $required = false;

    private mixed $default = null;

    /** @var array<string, string> valeur => libellé, dans l'ordre d'affichage */
    private array $options = [];

    /** @var list<mixed> */
    private array $rules = [];

    private function __construct(
        public readonly string $name,
        public readonly FieldType $type,
    ) {}

    public static function make(string $name, FieldType $type): self
    {
        return new self($name, $type);
    }

    public static function text(string $name): self
    {
        return new self($name, FieldType::TEXT);
    }

    public static function hidden(string $name): self
    {
        return new self($name, FieldType::HIDDEN);
    }

    public static function email(string $name): self
    {
        return new self($name, FieldType::EMAIL);
    }

    public static function password(string $name): self
    {
        return new self($name, FieldType::PASSWORD);
    }

    public static function number(string $name): self
    {
        return new self($name, FieldType::NUMBER);
    }

    public static function textarea(string $name): self
    {
        return new self($name, FieldType::TEXTAREA);
    }

    public static function select(string $name): self
    {
        return new self($name, FieldType::SELECT);
    }

    public static function radio(string $name): self
    {
        return new self($name, FieldType::RADIO);
    }

    public static function checkbox(string $name): self
    {
        return new self($name, FieldType::CHECKBOX);
    }

    public static function checkboxGroup(string $name): self
    {
        return new self($name, FieldType::CHECKBOX_GROUP);
    }

    public static function switch(string $name): self
    {
        return new self($name, FieldType::SWITCH);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function help(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;

        return $this;
    }

    /**
     * @param  array<string, string>  $options  valeur => libellé
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Règles propres au champ, en plus de celles qui découlent de son type et
     * de `required()` (ex. `max:255`).
     *
     * @param  list<mixed>  $rules
     */
    public function rules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Règles de validation complètes du champ, par nom (un groupe de cases ajoute
     * la règle de chacun de ses éléments).
     *
     * @return array<string, list<mixed>>
     */
    public function validationRules(): array
    {
        $values = array_map('strval', array_keys($this->options));
        $presence = $this->required ? ['required'] : ['nullable'];

        if ($this->type === FieldType::CHECKBOX_GROUP) {
            return [
                $this->name => [...$presence, 'array', ...$this->rules],
                $this->name.'.*' => ['string', Rule::in($values)],
            ];
        }

        return [$this->name => [...$presence, ...$this->type->rules($values), ...$this->rules]];
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Ce que l'app reçoit pour afficher le champ.
     *
     * @return array{name: string, type: string, label: string|null, placeholder: string|null, help: string|null, required: bool, default: mixed, options: list<array{value: string, label: string}>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'label' => $this->label,
            'placeholder' => $this->placeholder,
            'help' => $this->help,
            'required' => $this->required,
            'default' => $this->default,
            'options' => collect($this->options)
                ->map(fn (string $label, string|int $value): array => ['value' => (string) $value, 'label' => $label])
                ->values()
                ->all(),
        ];
    }
}
