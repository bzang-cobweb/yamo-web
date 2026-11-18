<?php

namespace Modules\Form\Filament;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field as FilamentField;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use LogicException;
use Modules\Form\Enums\FieldType;
use Modules\Form\Support\Field;

/**
 * Le champ Filament d'un `Field` : un même schéma décrit le formulaire de l'app,
 * la validation de l'API et le back-office (décision 2026-11-18-offres-et-domaines).
 * Libellé, aide, options, caractère obligatoire et règles de validation sont
 * repris du `Field`.
 */
final class FieldComponent
{
    /**
     * @param  string|null  $statePath  chemin de l'état, si différent du nom (ex. `attributes.surface` dans une colonne JSON)
     */
    public static function make(Field $field, ?string $statePath = null): FilamentField
    {
        $name = $statePath ?? $field->name;

        $component = match ($field->type) {
            FieldType::TEXT => TextInput::make($name)->maxLength($field->getMaxLength()),
            FieldType::NUMBER => TextInput::make($name)->numeric(),
            FieldType::TEXTAREA => Textarea::make($name)->maxLength($field->getMaxLength()),
            FieldType::SELECT, FieldType::SEGMENTED => Select::make($name)->options($field->optionLabels()),
            FieldType::RADIO => Radio::make($name)->options($field->optionLabels()),
            FieldType::CHECKBOX, FieldType::SWITCH => Toggle::make($name),
            FieldType::CHECKBOX_GROUP => CheckboxList::make($name)->options($field->optionLabels()),
            FieldType::DATE => DatePicker::make($name)->native(false)->format('Y-m-d'),
            default => throw new LogicException("Champ « {$field->name} » : type {$field->type->value} sans équivalent dans le back-office."),
        };

        $rules = $field->validationRules()[$field->name] ?? [];

        return $component
            ->label($field->getLabel())
            ->helperText($field->getHelp())
            ->required($field->isRequired())
            // Mêmes règles que l'API, hors présence (déjà portée par `required()`).
            ->rules(array_values(array_filter($rules, fn (mixed $rule): bool => ! in_array($rule, ['required', 'nullable'], true))));
    }
}
