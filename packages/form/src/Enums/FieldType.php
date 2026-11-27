<?php

namespace Bza\Form\Enums;

use Illuminate\Validation\Rule;

/**
 * Field types a form can describe. The mobile app serves one component
 * per type (decision 2026-11-15-contrat-mobile-features-formulaires): adding
 * one here requires its component on the mobile side.
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
    /** Date only, in `YYYY-MM-DD` format. */
    case DATE = 'date';
    /** Country (dialling code) + number, validated for the country; see `Field::phone()`. */
    case PHONE = 'phone';
    /** 6-digit verification code. */
    case CODE = 'code';
    /** One choice among a few options, as side-by-side buttons (e.g. « E-mail | Téléphone »). */
    case SEGMENTED = 'segmented';
    /** Multiple-choice dropdown: the list of chosen options (decision 2026-11-19-contrat-multiselect-intervalle-equipements). */
    case MULTISELECT = 'multiselect';
    /**
     * Multiple choices as chips on a scrolling line: the list of chosen options, like `multiselect`
     * (decision 2026-11-26-filtres-en-puces).
     */
    case CHIPS = 'chips';
    /** Range: `[min, max]`, each bound optional, max ≥ min; a two-thumb slider with `Field::slider()` (decision 2026-11-27-intervalle-curseur). */
    case RANGE = 'range';
    /** A rating on a slider, 1 to 5 by 0.1 by default, bounds and step set by `Field::ratingRange()` (decisions 2026-11-27-note-au-dixieme and 2026-11-27-note-bornes-etoiles). */
    case RATING = 'rating';
    /**
     * Files sent one by one before saving (decision 2026-11-19-contrat-champ-upload-photos):
     * the value is the ordered list of their identifiers; see `Field::upload()`.
     */
    case UPLOAD = 'upload';

    /**
     * A list of sub-field groups that the user adds and removes
     * (decision 2026-11-19-contrat-offer-informations): the value is the ordered
     * list of groups, each an object sub-field => value; see `Field::repeater()`.
     */
    case REPEATER = 'repeater';

    /**
     * The field offers a list of options.
     */
    public function hasOptions(): bool
    {
        return in_array($this, [self::SELECT, self::RADIO, self::SEGMENTED, self::CHECKBOX_GROUP, self::MULTISELECT, self::CHIPS, self::PHONE], true);
    }

    /**
     * Validation rules that follow from the type alone.
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
            self::CHECKBOX_GROUP, self::MULTISELECT, self::CHIPS => ['array'],
            self::RANGE => ['array', 'size:2'],
            self::DATE => ['date_format:Y-m-d'],
            self::PHONE => ['string', 'phone'],
            self::CODE => ['string', 'digits:6'],
            // A number; its bounds and increment come from the field (`Field::ratingRange()`, decision
            // 2026-11-27-note-bornes-etoiles).
            self::RATING => ['numeric'],
            self::UPLOAD, self::REPEATER => ['array'],
        };
    }
}
