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
    /** Date seule, au format `AAAA-MM-JJ`. */
    case DATE = 'date';
    /** Pays (indicatif) + numéro, validé selon le pays ; voir `Field::phone()`. */
    case PHONE = 'phone';
    /** Code de vérification à 6 chiffres. */
    case CODE = 'code';
    /** Choix parmi quelques options, en boutons côte à côte (ex. « E-mail | Téléphone »). */
    case SEGMENTED = 'segmented';
    /** Liste déroulante à choix multiples : la liste des options choisies (décision 2026-11-19-contrat-multiselect-intervalle-equipements). */
    case MULTISELECT = 'multiselect';
    /** Intervalle : `[min, max]`, chaque borne facultative, max ≥ min. */
    case RANGE = 'range';
    /** Note en étoiles, entier de 1 à 5 (décision 2026-11-19-contrat-avis-offres). */
    case RATING = 'rating';

    /**
     * Le champ propose une liste d'options.
     */
    public function hasOptions(): bool
    {
        return in_array($this, [self::SELECT, self::RADIO, self::SEGMENTED, self::CHECKBOX_GROUP, self::MULTISELECT, self::PHONE], true);
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
            self::SELECT, self::RADIO, self::SEGMENTED => ['string', Rule::in($optionValues)],
            self::CHECKBOX, self::SWITCH => ['boolean'],
            self::CHECKBOX_GROUP, self::MULTISELECT => ['array'],
            self::RANGE => ['array', 'size:2'],
            self::DATE => ['date_format:Y-m-d'],
            self::PHONE => ['string', 'phone'],
            self::CODE => ['string', 'digits:6'],
            self::RATING => ['integer', 'between:1,5'],
        };
    }
}
