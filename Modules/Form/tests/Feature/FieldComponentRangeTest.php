<?php

namespace Modules\Form\Tests\Feature;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Modules\Form\Filament\FieldComponent;
use Modules\Form\Support\Field;
use Tests\TestCase;

/**
 * Un intervalle dans le back-office : deux nombres côte à côte (décision
 * 2026-11-19-contrat-multiselect-intervalle-equipements) ; tout autre champ reste
 * le composant de `make()`.
 */
class FieldComponentRangeTest extends TestCase
{
    public function test_a_range_becomes_two_numbers_side_by_side(): void
    {
        $component = FieldComponent::component(Field::range('budget')->label('Budget')->suffix('FCFA')->rules(['min:0']), 'details.budget');

        $this->assertInstanceOf(Fieldset::class, $component);
        $children = $component->getDefaultChildComponents();
        $this->assertCount(2, $children);
        $this->assertContainsOnlyInstancesOf(TextInput::class, $children);
        $this->assertSame(['details.budget.0', 'details.budget.1'], array_map(fn (TextInput $input): string => $input->getName(), $children));
    }

    public function test_another_field_keeps_its_component(): void
    {
        $this->assertInstanceOf(TextInput::class, FieldComponent::component(Field::number('surface')));
    }
}
