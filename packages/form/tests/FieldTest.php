<?php

namespace Bza\Form\Tests;

use Bza\Form\Enums\FieldType;
use Bza\Form\Rules\MinImageSide;
use Bza\Form\Support\ContainsSearch;
use Bza\Form\Support\Field;
use Bza\Form\Support\Locales;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use LogicException;

/**
 * One field description drives what the app displays and what the server accepts.
 */
class FieldTest extends TestCase
{
    /**
     * @param  list<Field>  $fields
     * @param  array<string, mixed>  $data
     */
    private function passes(array $fields, array $data): bool
    {
        $rules = array_merge(...array_map(fn (Field $field): array => $field->validationRules(), $fields));

        return Validator::make($data, $rules)->passes();
    }

    public function test_the_type_gives_the_rules_and_required_the_presence(): void
    {
        $email = Field::email('email')->required();
        $age = Field::number('age');

        $this->assertSame(['email' => ['required', 'string', 'email']], $email->validationRules());
        $this->assertTrue($this->passes([$email, $age], ['email' => 'ada@example.test']));
        $this->assertFalse($this->passes([$email, $age], ['email' => 'ada@example.test', 'age' => 'old']));
        $this->assertFalse($this->passes([$email], []));
    }

    public function test_options_are_served_in_order_and_enforced(): void
    {
        $field = Field::chips('colors')->options(['red' => 'Red', 'blue' => 'Blue']);

        $this->assertSame(['red' => 'Red', 'blue' => 'Blue'], $field->optionLabels());
        $this->assertSame(['red', 'blue'], array_column($field->toArray()['options'], 'value'));
        $this->assertTrue($this->passes([$field], ['colors' => ['blue', 'red']]));
        $this->assertFalse($this->passes([$field], ['colors' => ['green']]));
    }

    public function test_a_searchable_list_serves_its_limit_only_when_given(): void
    {
        // Decision 2026-11-27-select-limite: without a limit, the app's default.
        $this->assertArrayNotHasKey('search_limit', Field::select('city')->searchable()->toArray());
        $this->assertSame([true, 25], [Field::select('city')->searchable(limit: 25)->toArray()['searchable'], Field::select('city')->searchable(limit: 25)->toArray()['search_limit']]);
    }

    public function test_a_field_hidden_by_its_condition_is_not_validated(): void
    {
        $kind = Field::select('kind')->options(['person' => 'Person', 'company' => 'Company'])->required();
        $company = Field::text('company')->required()->visibleWhen('kind', 'company');

        $this->assertTrue($this->passes([$kind, $company], ['kind' => 'person']));
        $this->assertFalse($this->passes([$kind, $company], ['kind' => 'company']));
        $this->assertSame(['field' => 'kind', 'value' => 'company', 'values' => ['company']], $company->toArray()['visible_when']);
    }

    public function test_a_range_keeps_its_bounds_in_order(): void
    {
        $price = Field::range('price')->rules(['min:0']);

        $this->assertTrue($this->passes([$price], ['price' => [10, null]]));
        $this->assertFalse($this->passes([$price], ['price' => [20, 10]]));
        $this->assertFalse($this->passes([$price], ['price' => [-1, 10]]));
    }

    public function test_a_readonly_field_is_shown_but_never_validated(): void
    {
        $field = Field::text('code')->required()->readonly();

        $this->assertSame([], $field->validationRules());
        $this->assertTrue($field->toArray()['readonly']);
    }

    public function test_file_settings_belong_to_file_fields_only(): void
    {
        $this->expectException(LogicException::class);
        Field::text('name')->maxFiles(3);
    }

    public function test_values_are_displayed_in_the_requested_language(): void
    {
        $this->assertSame('Blue', Field::select('color')->options(['blue' => 'Blue'])->displayValue('blue', 'en'));
        $this->assertSame('1,500 m²', Field::number('area')->suffix('m²')->displayValue(1500, 'en'));
        $this->assertNull(Field::text('empty')->displayValue('', 'en'));
    }

    public function test_every_type_has_rules(): void
    {
        foreach (FieldType::cases() as $type) {
            $this->assertNotSame([], $type->rules([]), $type->value);
        }
    }

    public function test_an_image_must_be_large_enough_on_its_longest_side(): void
    {
        $rule = ['file' => [new MinImageSide(800)]];

        $this->assertTrue(Validator::make(['file' => UploadedFile::fake()->image('banner.png', 1600, 400)], $rule)->passes());
        $this->assertTrue(Validator::make(['file' => UploadedFile::fake()->image('small.png', 400, 300)], $rule)->fails());
    }

    public function test_the_contains_pattern_matches_wildcards_literally(): void
    {
        $this->assertSame('%50!% off!_now!!%', ContainsSearch::pattern('50% OFF_now!'));
    }

    public function test_languages_come_from_the_package_configuration_first(): void
    {
        config(['form.locales' => ['de', 'fr'], 'form.default_locale' => 'de']);

        $this->assertSame(['de' => 'Deutsch', 'fr' => 'Français'], Locales::options());
        $this->assertSame('de', Locales::default());
    }
}
