<?php

namespace Modules\Form\Enums;

use Illuminate\Validation\Rule;

/**
 * Types de champ qu'un formulaire peut décrire. L'app mobile sert un composant
 * par type (décision 2026-11-15-contrat-mobile-features-formulaires) : en ajouter
 * un ici demande son composant côté mobile.
 */
enum FieldType: string
{
    case TEXT = 'text';
    case HIDDEN = 'hidden';
    case EMAIL = 'email';
    case PASSWORD = 'password';
    case NUMBER = 'number';
    case TEXTAREA = 'textarea';
    case SELECT = 'select';
    case RADIO = 'radio';
    case CHECKBOX = 'checkbox';
    case CHECKBOX_GROUP = 'checkbox_group';
    case SWITCH = 'switch';

    /**
     * Le champ propose une liste d'options.
     */
    public function hasOptions(): bool
    {
        return in_array($this, [self::SELECT, self::RADIO, self::CHECKBOX_GROUP], true);
    }

    /**
     * Règles de validation qui découlent du type seul.
     *
     * @param  list<string>  $optionValues
     * @return list<mixed>
     */
    public function rules(array $optionValues): array
    {
        return match ($this) {
            self::TEXT, self::HIDDEN, self::PASSWORD, self::TEXTAREA => ['string'],
            self::EMAIL => ['string', 'email'],
            self::NUMBER => ['numeric'],
            self::SELECT, self::RADIO => ['string', Rule::in($optionValues)],
            self::CHECKBOX, self::SWITCH => ['boolean'],
            self::CHECKBOX_GROUP => ['array'],
        };
    }
}
