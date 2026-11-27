<?php

namespace Bza\Form\Filament;

use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field as FilamentField;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\GridDirection;
use LogicException;
use Bza\Form\Enums\FieldType;
use Bza\Form\Support\Field;

/**
 * The Filament field of a `Field`: one schema describes the app's form,
 * the API validation and the back office (decision 2026-11-18-offres-et-domaines).
 * Label, help, options, required flag and validation rules are
 * taken from the `Field`.
 */
final class FieldComponent
{
    /**
     * The component of a `Field`, whatever its type: a range (`range`)
     * becomes two side-by-side numbers, « Min » and « Max » (decision
     * 2026-11-19-contrat-multiselect-intervalle-equipements); any other type, the
     * field from `make()`.
     */
    public static function component(Field $field, ?string $statePath = null): Component
    {
        if ($field->type !== FieldType::RANGE) {
            return self::make($field, $statePath);
        }

        $name = $statePath ?? $field->name;
        $rules = array_values(array_filter($field->validationRules()[$field->name.'.*'] ?? [], fn (mixed $rule): bool => is_string($rule) && $rule !== 'nullable'));
        $bound = fn (int $index, string $label): TextInput => TextInput::make("{$name}.{$index}")
            ->label($label)
            ->numeric()
            ->suffix($field->getSuffix())
            ->rules($rules);

        return Fieldset::make($field->getLabel() ?? $field->name)
            ->columns(2)
            ->schema([
                $bound(0, __('form::field.range_min')),
                // The max bound at least equal to the min, when both are given.
                $bound(1, __('form::field.range_max'))
                    ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $name): void {
                        $min = $get("{$name}.0");
                        if (is_numeric($min) && is_numeric($value) && (float) $value < (float) $min) {
                            $fail(__('form::validation.range_order'));
                        }
                    }),
            ]);
    }

    /** The maximum height of a checkbox list, as in the app (decision 2026-11-25-cases-hauteur-fixe). */
    public const CHECKBOX_LIST_MAX_HEIGHT = '16rem';

    /**
     * A fixed-height checkbox list: beyond it, it scrolls within its frame, as in the app (decision
     * 2026-11-25-cases-hauteur-fixe).
     */
    public static function boundedCheckboxList(CheckboxList $list): CheckboxList
    {
        // As a grid by rows: in CSS columns (the default), a list taller than the frame would overflow sideways
        // and run over the border instead of scrolling.
        return $list->gridDirection(GridDirection::Row)->extraAttributes([
            'style' => 'max-height: '.self::CHECKBOX_LIST_MAX_HEIGHT.'; overflow-y: auto; padding: 0.75rem; border: 1px solid rgb(127 127 127 / 0.25); border-radius: 0.5rem;',
        ]);
    }

    /**
     * @param  string|null  $statePath  state path, if different from the name (e.g. `attributes.surface` in a JSON column)
     */
    public static function make(Field $field, ?string $statePath = null): FilamentField
    {
        $name = $statePath ?? $field->name;

        // A required Yes / No choice: a toggle, which saves « yes » or « no »
        // (decision 2026-11-23-regles-oui-non-obligatoires).
        if ($field->type === FieldType::SELECT && $field->isRequired() && array_keys($field->optionLabels()) === ['yes', 'no']) {
            $toggle = Toggle::make($name)
                ->default(false)
                ->formatStateUsing(fn (mixed $state): bool => $state === 'yes' || $state === true)
                ->dehydrateStateUsing(fn (mixed $state): string => $state ? 'yes' : 'no')
                ->label($field->getLabel())
                ->helperText($field->getHelp());

            $condition = $field->getVisibleWhen();
            if ($condition !== null) {
                $prefix = str_contains($name, '.') ? substr($name, 0, (int) strrpos($name, '.') + 1) : '';
                $toggle->visible(fn (Get $get): bool => in_array(Field::conditionValue($get($prefix.$condition['field'])), $condition['values'], true));
            }

            return $toggle;
        }

        $component = match ($field->type) {
            FieldType::TEXT => TextInput::make($name)->maxLength($field->getMaxLength()),
            FieldType::NUMBER => TextInput::make($name)->numeric(),
            FieldType::TEXTAREA => Textarea::make($name)->maxLength($field->getMaxLength()),
            FieldType::SELECT, FieldType::SEGMENTED => Select::make($name)->options($field->optionLabels()),
            FieldType::RADIO => Radio::make($name)->options($field->optionLabels()),
            FieldType::CHECKBOX, FieldType::SWITCH => Toggle::make($name),
            FieldType::CHECKBOX_GROUP => self::boundedCheckboxList(CheckboxList::make($name)->options($field->optionLabels())),
            FieldType::MULTISELECT, FieldType::CHIPS => Select::make($name)->multiple()->options($field->optionLabels()),
            // A bounded number, at the field's increment (decisions 2026-11-27-note-au-dixieme and 2026-11-27-note-bornes-etoiles).
            FieldType::RATING => TextInput::make($name)->numeric()->minValue($field->getBounds()['min'] ?? null)->maxValue($field->getBounds()['max'] ?? null)->step($field->getBounds()['step'] ?? 'any'),
            FieldType::DATE => DatePicker::make($name)->native(false)->format('Y-m-d'),
            default => throw new LogicException("Champ « {$field->name} » : type {$field->type->value} sans équivalent dans le back-office."),
        };

        // No `exclude_unless`: it names the sibling field without the state prefix (`rental_type` and
        // not `details.rental_type`) and would always exclude the value; the visibility condition
        // below replaces it, since a hidden field is not saved.
        $rules = array_values(array_filter($field->validationRules()[$field->name] ?? [], fn (mixed $rule): bool => ! is_string($rule) || ! str_starts_with($rule, 'exclude_unless:')));

        // A checkbox changes the visibility of other fields: the form follows its value.
        if (in_array($field->type, [FieldType::CHECKBOX, FieldType::SWITCH], true)) {
            $component->live();
        }

        // Visibility condition: the sibling field (same state prefix) has the expected value.
        $condition = $field->getVisibleWhen();
        if ($condition !== null) {
            $prefix = str_contains($name, '.') ? substr($name, 0, (int) strrpos($name, '.') + 1) : '';
            $component->visible(fn (Get $get): bool => in_array(Field::conditionValue($get($prefix.$condition['field'])), $condition['values'], true));
        }

        return $component
            ->label($field->getLabel())
            ->helperText($field->getHelp())
            ->required($field->isRequired())
            // Same rules as the API, except presence (already carried by `required()`).
            ->rules(array_values(array_filter($rules, fn (mixed $rule): bool => ! in_array($rule, ['required', 'nullable'], true))));
    }
}
