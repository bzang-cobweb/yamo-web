<?php

namespace Modules\Form\Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Modules\Form\Enums\FieldType;
use Modules\Form\Support\Field;
use Modules\Form\Support\Form;
use Tests\TestCase;

/**
 * Un champ déclare à la fois ce que l'app affiche et ce que le serveur valide.
 */
class FieldTest extends TestCase
{
    private function form(): Form
    {
        return new class extends Form
        {
            public static function name(): string
            {
                return 'test.all';
            }

            public function fields(): array
            {
                return [
                    Field::text('nickname')->label('Pseudonyme')->required()->rules(['max:5']),
                    Field::hidden('token'),
                    Field::email('email')->required(),
                    Field::password('password')->required(),
                    Field::number('age'),
                    Field::textarea('bio'),
                    Field::select('city')->options(['ge' => 'Genève', 'vd' => 'Vaud'])->required(),
                    Field::radio('size')->options(['s' => 'S', 'm' => 'M']),
                    Field::checkbox('terms')->required(),
                    Field::checkboxGroup('tags')->options(['a' => 'A', 'b' => 'B']),
                    Field::switch('newsletter'),
                ];
            }

            public function submitLabel(): string
            {
                return 'Envoyer';
            }
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function errors(array $data): array
    {
        return Validator::make($data, $this->form()->rules())->errors()->keys();
    }

    private function valid(): array
    {
        return [
            'nickname' => 'abc', 'email' => 'a@example.com', 'password' => 'x', 'city' => 'ge', 'terms' => true,
        ];
    }

    public function test_every_type_is_covered_by_the_test_form(): void
    {
        $types = array_map(fn (Field $field): string => $field->type->value, $this->form()->fields());

        $this->assertEqualsCanonicalizing(array_column(FieldType::cases(), 'value'), $types);
    }

    public function test_valid_data_passes(): void
    {
        $this->assertSame([], $this->errors($this->valid()));
        $this->assertSame([], $this->errors([
            ...$this->valid(), 'token' => 't', 'age' => '42', 'bio' => 'Bonjour', 'size' => 'm',
            'tags' => ['a', 'b'], 'newsletter' => false,
        ]));
    }

    public function test_required_fields_are_required(): void
    {
        $this->assertEqualsCanonicalizing(['nickname', 'email', 'password', 'city', 'terms'], $this->errors([]));
    }

    public function test_type_rules_are_derived(): void
    {
        $errors = $this->errors([
            ...$this->valid(),
            'nickname' => 'trop long',
            'email' => 'pas-un-email',
            'age' => 'douze',
            'city' => 'zh',
            'size' => 'xl',
            'terms' => 'peut-être',
            'tags' => ['a', 'z'],
            'newsletter' => 'oui',
        ]);

        $this->assertEqualsCanonicalizing(
            ['nickname', 'email', 'age', 'city', 'size', 'terms', 'tags.1', 'newsletter'],
            $errors,
        );
    }

    public function test_serialization_keeps_option_order_and_metadata(): void
    {
        $city = collect($this->form()->fields())->firstWhere('name', 'city')->toArray();

        $this->assertSame([
            'name' => 'city',
            'type' => 'select',
            'label' => null,
            'placeholder' => null,
            'help' => null,
            'required' => true,
            'default' => null,
            'options' => [['value' => 'ge', 'label' => 'Genève'], ['value' => 'vd', 'label' => 'Vaud']],
        ], $city);
    }

    public function test_attributes_use_field_labels(): void
    {
        $this->assertSame(['nickname' => 'Pseudonyme'], $this->form()->attributes());
    }
}
