<?php

namespace Modules\Form\Filament;

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
     * Le composant d'un `Field`, quel que soit son type : un intervalle (`range`)
     * devient deux nombres côte à côte, « Min » et « Max » (décision
     * 2026-11-19-contrat-multiselect-intervalle-equipements) ; tout autre type, le
     * champ de `make()`.
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
                // La borne max au moins égale à la min, quand les deux sont données.
                $bound(1, __('form::field.range_max'))
                    ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $name): void {
                        $min = $get("{$name}.0");
                        if (is_numeric($min) && is_numeric($value) && (float) $value < (float) $min) {
                            $fail(__('form::validation.range_order'));
                        }
                    }),
            ]);
    }

    /** La hauteur au plus d'une liste de cases, comme dans l'app (décision 2026-11-25-cases-hauteur-fixe). */
    public const CHECKBOX_LIST_MAX_HEIGHT = '16rem';

    /**
     * Une liste de cases à hauteur fixe : au-delà, elle défile dans son cadre, comme dans l'app (décision
     * 2026-11-25-cases-hauteur-fixe).
     */
    public static function boundedCheckboxList(CheckboxList $list): CheckboxList
    {
        return $list->extraAttributes([
            'style' => 'max-height: '.self::CHECKBOX_LIST_MAX_HEIGHT.'; overflow-y: auto; padding: 0.75rem; border: 1px solid rgb(127 127 127 / 0.25); border-radius: 0.5rem;',
        ]);
    }

    /**
     * @param  string|null  $statePath  chemin de l'état, si différent du nom (ex. `attributes.surface` dans une colonne JSON)
     */
    public static function make(Field $field, ?string $statePath = null): FilamentField
    {
        $name = $statePath ?? $field->name;

        // Un choix Oui / Non obligatoire : un interrupteur, qui enregistre « yes » ou « no »
        // (décision 2026-11-23-regles-oui-non-obligatoires).
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
            FieldType::MULTISELECT => Select::make($name)->multiple()->options($field->optionLabels()),
            FieldType::RATING => Select::make($name)->options(array_combine(range(1, 5), array_map(fn (int $stars): string => str_repeat('★', $stars), range(1, 5)))),
            FieldType::DATE => DatePicker::make($name)->native(false)->format('Y-m-d'),
            default => throw new LogicException("Champ « {$field->name} » : type {$field->type->value} sans équivalent dans le back-office."),
        };

        // Sans `exclude_unless` : il nomme le champ voisin sans le préfixe d'état (`rental_type` et
        // non `details.rental_type`) et exclurait toujours la valeur ; la condition d'affichage
        // ci-dessous en tient lieu, un champ masqué n'étant pas enregistré.
        $rules = array_values(array_filter($field->validationRules()[$field->name] ?? [], fn (mixed $rule): bool => ! is_string($rule) || ! str_starts_with($rule, 'exclude_unless:')));

        // Une case change l'affichage d'autres champs : le formulaire suit sa valeur.
        if (in_array($field->type, [FieldType::CHECKBOX, FieldType::SWITCH], true)) {
            $component->live();
        }

        // Condition d'affichage : le champ voisin (même préfixe d'état) a la valeur attendue.
        $condition = $field->getVisibleWhen();
        if ($condition !== null) {
            $prefix = str_contains($name, '.') ? substr($name, 0, (int) strrpos($name, '.') + 1) : '';
            $component->visible(fn (Get $get): bool => in_array(Field::conditionValue($get($prefix.$condition['field'])), $condition['values'], true));
        }

        return $component
            ->label($field->getLabel())
            ->helperText($field->getHelp())
            ->required($field->isRequired())
            // Mêmes règles que l'API, hors présence (déjà portée par `required()`).
            ->rules(array_values(array_filter($rules, fn (mixed $rule): bool => ! in_array($rule, ['required', 'nullable'], true))));
    }
}
