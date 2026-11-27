<?php

namespace Tests\Feature\Form;

use Illuminate\Support\Facades\Validator;
use LogicException;
use Bza\Form\Enums\FieldType;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
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
                    Field::date('birthday'),
                    Field::phone('mobile')->options(['CH' => ['label' => 'Suisse', 'hint' => '+41'], 'FR' => ['label' => 'France', 'hint' => '+33']]),
                    Field::code('otp'),
                    Field::rating('stars'),
                    Field::upload('pictures')->accept(['image/jpeg'])->maxSize(500)->maxFiles(3),
                    Field::repeater('notes')->maxItems(2)->addLabel('Ajouter')->fields([
                        Field::hidden('id'),
                        Field::text('title')->required()->maxLength(5),
                    ]),
                    Field::multiselect('colors')->options(['red' => 'Rouge', 'blue' => 'Bleu']),
                    // Des puces : la liste des options choisies, comme une liste à choix multiples (décision 2026-11-26-filtres-en-puces).
                    Field::chips('moods')->options(['calm' => 'Calme', 'bright' => 'Lumineux']),
                    Field::range('budget')->rules(['min:0']),
                    Field::segmented('contact')->options(['mail' => 'E-mail', 'sms' => 'SMS']),
                    Field::text('pager')->visibleWhen('contact', 'sms')->required(),
                    Field::text('locked')->readonly()->required(),
                    Field::select('canton')->dependsOn('city')->options(['g1' => ['label' => 'Genève-ville', 'parent' => 'ge'], 'v1' => ['label' => 'Lausanne', 'parent' => 'vd']]),
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

    public function test_a_date_field_serves_its_display_formats_from_the_configuration(): void
    {
        // Décision 2026-11-25-dates-formatees-serveur : changer le format suffit côté serveur.
        $this->assertSame(['day' => 'numeric', 'month' => 'long', 'year' => 'numeric'], Field::date('birth_date')->toArray()['date_formats']['value']);
        config(['form.date_formats.value' => ['month' => 'short', 'year' => 'numeric']]);
        $this->assertSame(['month' => 'short', 'year' => 'numeric'], Field::date('birth_date')->toArray()['date_formats']['value']);
        $this->assertNull(Field::text('name')->toArray()['date_formats']);
    }

    public function test_every_type_is_covered_by_the_test_form(): void
    {
        $types = array_map(fn (Field $field): string => $field->type->value, $this->form()->fields());

        $this->assertEqualsCanonicalizing(array_column(FieldType::cases(), 'value'), array_values(array_unique($types)));
    }

    public function test_a_rating_goes_from_1_to_5_by_tenths(): void
    {
        // Décision 2026-11-27-note-au-dixieme.
        foreach ([0, 0.9, 5.1, 6, 2.55, 'beaucoup'] as $invalid) {
            $this->assertContains('stars', $this->errors([...$this->valid(), 'stars' => $invalid]), (string) $invalid);
        }
        foreach ([1, 5, '3', 3.2, '4.7'] as $valid) {
            $this->assertNotContains('stars', $this->errors([...$this->valid(), 'stars' => $valid]), (string) $valid);
        }
        $this->assertSame('★★★★☆ 4,0', Field::rating('stars')->displayValue(4, 'fr'));
        $this->assertSame('★★★☆☆ 3,2', Field::rating('stars')->displayValue(3.2, 'fr'));
    }

    public function test_a_rating_serves_its_range_and_stars_and_is_validated_against_them(): void
    {
        // Décision 2026-11-27-note-bornes-etoiles : 1 à 5 et les étoiles par défaut.
        $this->assertSame([['min' => 1.0, 'max' => 5.0, 'step' => 0.1], true], [Field::rating('stars')->toArray()['range'], Field::rating('stars')->toArray()['stars']]);
        $this->assertSame([null, null], [Field::text('name')->toArray()['range'], Field::text('name')->toArray()['stars']]);

        $field = Field::rating('score')->ratingRange(0, 10, 0.5)->stars(false);
        $this->assertSame([['min' => 0.0, 'max' => 10.0, 'step' => 0.5], false], [$field->toArray()['range'], $field->toArray()['stars']]);
        $rules = $field->validationRules();
        $this->assertTrue(Validator::make(['score' => 7.5], $rules)->passes());
        $this->assertTrue(Validator::make(['score' => 0], $rules)->passes());
        $this->assertFalse(Validator::make(['score' => 10.5], $rules)->passes());
        $this->assertFalse(Validator::make(['score' => 7.2], $rules)->passes());

        $this->expectException(LogicException::class);
        Field::rating('score')->ratingRange(5, 1);
    }

    public function test_a_range_can_be_a_two_thumb_slider_with_bounds_and_step(): void
    {
        // Décision 2026-11-27-intervalle-curseur : sans curseur, deux saisies et pas de bornes servies.
        $this->assertNull(Field::range('price')->toArray()['range']);

        $field = Field::range('price')->slider(0, 500000, 5000);
        $this->assertSame(['min' => 0.0, 'max' => 500000.0, 'step' => 5000.0], $field->toArray()['range']);
        $rules = $field->validationRules();
        $this->assertTrue(Validator::make(['price' => [50000, 200000]], $rules)->passes());
        $this->assertTrue(Validator::make(['price' => [null, 200000]], $rules)->passes());
        $this->assertFalse(Validator::make(['price' => [50000, 600000]], $rules)->passes());
        $this->assertFalse(Validator::make(['price' => [52000, 200000]], $rules)->passes());
        $this->assertFalse(Validator::make(['price' => [200000, 50000]], $rules)->passes());

        $this->expectException(LogicException::class);
        Field::text('name')->slider(0, 10);
    }

    public function test_a_multiselect_and_a_range_are_validated(): void
    {
        $this->assertNotContains('colors.0', $this->errors([...$this->valid(), 'colors' => ['red', 'blue']]));
        $this->assertContains('colors.1', $this->errors([...$this->valid(), 'colors' => ['red', 'green']]));
        $this->assertNotContains('moods.0', $this->errors([...$this->valid(), 'moods' => ['calm']]));
        $this->assertContains('moods.0', $this->errors([...$this->valid(), 'moods' => ['noisy']]));
        foreach ([[null, '100'], ['50', null], ['50', '100'], ['5', '5']] as $valid) {
            $this->assertSame([], array_values(array_filter($this->errors([...$this->valid(), 'budget' => $valid]), fn (string $key): bool => str_starts_with($key, 'budget'))), json_encode($valid));
        }
        $this->assertContains('budget', $this->errors([...$this->valid(), 'budget' => ['100', '50']]));
        $this->assertContains('budget.0', $this->errors([...$this->valid(), 'budget' => ['-1', '50']]));
        $this->assertContains('budget', $this->errors([...$this->valid(), 'budget' => ['1']]));
        $this->assertSame('50 – 100', Field::range('budget')->displayValue(['50', '100'], 'fr'));
        $this->assertSame('≤ 100', Field::range('budget')->displayValue([null, '100'], 'fr'));
        $this->assertSame('Rouge, Bleu', Field::multiselect('colors')->options(['red' => 'Rouge', 'blue' => 'Bleu'])->displayValue(['red', 'blue'], 'fr'));
    }

    public function test_valid_data_passes(): void
    {
        $this->assertSame([], $this->errors($this->valid()));
        $this->assertSame([], $this->errors([
            ...$this->valid(), 'token' => 't', 'age' => '42', 'bio' => 'Bonjour', 'size' => 'm',
            'tags' => ['a', 'b'], 'newsletter' => false, 'stars' => 4,
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
            'label_hidden' => false,
            'placeholder' => null,
            'help' => null,
            'notice' => null,
            'required' => true,
            'readonly' => false,
            'searchable' => false,
            'locked' => false,
            'depends_on' => null,
            'visible_when' => null,
            'reloads' => false,
            'suggestions' => null,
            'max_length' => null,
            'range' => null,
            'stars' => null,
            'date_formats' => null,
            'calendar' => null,
            'suffix' => null,
            'suffix_when' => null,
            'upload' => null,
            'repeater' => null,
            'default' => null,
            'options' => [
                ['value' => 'ge', 'label' => 'Genève', 'hint' => null, 'parent' => null],
                ['value' => 'vd', 'label' => 'Vaud', 'hint' => null, 'parent' => null],
            ],
        ], $city);
    }

    public function test_a_repeater_validates_each_item_and_its_maximum(): void
    {
        // Décision 2026-11-19-contrat-offer-informations.
        $this->assertNotContains('notes', $this->errors($this->valid()));
        $this->assertSame([], array_values(array_filter($this->errors([...$this->valid(), 'notes' => [['title' => 'Un'], ['id' => '7', 'title' => 'Deux']]]), fn (string $key): bool => str_starts_with($key, 'notes'))));

        $errors = $this->errors([...$this->valid(), 'notes' => [['title' => ''], ['title' => 'Beaucoup trop long'], ['title' => 'Trois']]]);
        $this->assertContains('notes', $errors);
        $this->assertContains('notes.0.title', $errors);
        $this->assertContains('notes.1.title', $errors);

        $notes = collect($this->form()->fields())->firstWhere('name', 'notes')->toArray();
        $this->assertSame(2, $notes['repeater']['max_items']);
        $this->assertSame('Ajouter', $notes['repeater']['add_label']);
        $this->assertSame(['id', 'title'], array_column($notes['repeater']['fields'], 'name'));
    }

    public function test_a_field_hidden_by_its_condition_is_neither_required_nor_kept(): void
    {
        $this->assertNotContains('pager', $this->errors($this->valid()));
        $this->assertNotContains('pager', $this->errors([...$this->valid(), 'contact' => 'mail']));
        $this->assertContains('pager', $this->errors([...$this->valid(), 'contact' => 'sms']));
        $this->assertContains('contact', $this->errors([...$this->valid(), 'contact' => 'fax']));

        $validated = Validator::make([...$this->valid(), 'contact' => 'mail', 'pager' => '123'], $this->form()->rules())->validated();
        $this->assertArrayNotHasKey('pager', $validated);

        $pager = collect($this->form()->fields())->firstWhere('name', 'pager')->toArray();
        $this->assertSame(['field' => 'contact', 'value' => 'sms', 'values' => ['sms']], $pager['visible_when']);
    }

    public function test_a_single_option_is_chosen_and_locked_but_still_validated(): void
    {
        $country = Field::select('country')->options(['ch' => 'Suisse'])->required();
        $phone = Field::phone('mobile')->options(['CH' => ['label' => 'Suisse', 'hint' => '+41']])->default(['country' => null, 'number' => '079']);
        $region = Field::select('region')->dependsOn('country')->options(['vd' => ['label' => 'Vaud', 'parent' => 'ch']]);

        $this->assertTrue($country->toArray()['locked']);
        $this->assertSame('ch', $country->toArray()['default']);
        $this->assertSame(['country' => 'CH', 'number' => '079'], $phone->toArray()['default']);
        $this->assertTrue($phone->toArray()['locked']);
        // Une liste dépendante n'est jamais verrouillée : ses options suivent le parent.
        $this->assertFalse($region->toArray()['locked']);
        // Verrouillé n'est pas lecture seule : la valeur est validée.
        $this->assertArrayHasKey('country', $country->validationRules());
        $this->assertTrue(Validator::make(['country' => 'fr'], $country->validationRules())->fails());
        $this->assertFalse(Field::select('city')->options(['ge' => 'Genève', 'vd' => 'Vaud'])->toArray()['locked']);
    }

    public function test_attributes_use_field_labels(): void
    {
        $this->assertSame(['nickname' => 'Pseudonyme'], $this->form()->attributes());
    }

    public function test_a_date_field_accepts_only_yyyy_mm_dd_and_is_optional_unless_required(): void
    {
        $this->assertNotContains('birthday', $this->errors($this->valid()));
        $this->assertNotContains('birthday', $this->errors([...$this->valid(), 'birthday' => null]));
        $this->assertNotContains('birthday', $this->errors([...$this->valid(), 'birthday' => '1990-04-12']));
        $this->assertContains('birthday', $this->errors([...$this->valid(), 'birthday' => '12.04.1990']));
        $this->assertContains('birthday', $this->errors([...$this->valid(), 'birthday' => '1990-02-30']));
    }

    public function test_the_required_flag_is_sent_and_drives_the_rule(): void
    {
        $fields = collect($this->form()->fields())->keyBy('name');

        $this->assertTrue($fields['nickname']->toArray()['required']);
        $this->assertFalse($fields['birthday']->toArray()['required']);
        $this->assertSame('required', $this->form()->rules()['nickname'][0]);
        $this->assertSame('nullable', $this->form()->rules()['birthday'][0]);
    }

    public function test_a_readonly_field_is_sent_but_never_validated(): void
    {
        $locked = collect($this->form()->fields())->firstWhere('name', 'locked');

        $this->assertTrue($locked->toArray()['readonly']);
        $this->assertArrayNotHasKey('locked', $this->form()->rules());
        $this->assertNotContains('locked', $this->errors($this->valid()));
    }

    public function test_a_dependent_option_must_belong_to_the_chosen_parent(): void
    {
        $canton = collect($this->form()->fields())->firstWhere('name', 'canton')->toArray();
        $this->assertSame('city', $canton['depends_on']);
        $this->assertSame('ge', $canton['options'][0]['parent']);

        $this->assertNotContains('canton', $this->errors([...$this->valid(), 'city' => 'ge', 'canton' => 'g1']));
        $this->assertContains('canton', $this->errors([...$this->valid(), 'city' => 'ge', 'canton' => 'v1']));
    }

    public function test_a_phone_is_checked_against_the_chosen_country(): void
    {
        $this->assertNotContains('mobile', $this->errors([...$this->valid(), 'mobile' => '079 123 45 67', 'mobile_country' => 'CH']));
        $this->assertContains('mobile', $this->errors([...$this->valid(), 'mobile' => '123', 'mobile_country' => 'CH']));
        $this->assertContains('mobile_country', $this->errors([...$this->valid(), 'mobile' => '079 123 45 67']));
        $this->assertContains('mobile_country', $this->errors([...$this->valid(), 'mobile' => '079 123 45 67', 'mobile_country' => 'DE']));
    }

    public function test_a_code_has_exactly_six_digits(): void
    {
        $this->assertNotContains('otp', $this->errors([...$this->valid(), 'otp' => '012345']));
        $this->assertContains('otp', $this->errors([...$this->valid(), 'otp' => '12345']));
        $this->assertContains('otp', $this->errors([...$this->valid(), 'otp' => '12a456']));
    }

    public function test_a_textarea_is_limited_to_1000_characters_by_default(): void
    {
        $bio = Field::textarea('bio');

        $this->assertSame(1000, $bio->toArray()['max_length']);
        $this->assertNotContains('bio', $this->errors([...$this->valid(), 'bio' => str_repeat('a', 1000)]));
        $this->assertContains('bio', $this->errors([...$this->valid(), 'bio' => str_repeat('a', 1001)]));
    }

    public function test_a_textarea_limit_can_be_changed_per_field(): void
    {
        $field = Field::textarea('note')->maxLength(5);

        $this->assertSame(5, $field->toArray()['max_length']);
        $this->assertTrue(Validator::make(['note' => 'abcde'], $field->validationRules())->passes());
        $this->assertFalse(Validator::make(['note' => 'abcdef'], $field->validationRules())->passes());
    }

    public function test_a_date_serves_its_calendar_bounds(): void
    {
        // Décision 2026-11-19-bornes-des-dates : sans borne par défaut ; bornée à la demande.
        $this->assertSame(['min' => null, 'max' => null, 'step' => null], Field::date('available_from')->toArray()['range']);
        $this->assertSame(['min' => '2026-11-19', 'max' => '2027-11-19', 'step' => null], Field::date('check_in')->dateRange('2026-11-19', '2027-11-19')->toArray()['range']);

        $this->expectException(LogicException::class);
        Field::text('name')->dateRange('2026-11-19', null);
    }

    public function test_a_field_can_carry_a_notice_shown_before_its_label(): void
    {
        // Décision 2026-11-22-champ-note.
        $field = Field::checkbox('accept_other_visits')->label('Accepter aussi')->notice('2 autres visites ce jour-là.');

        $this->assertSame('2 autres visites ce jour-là.', $field->toArray()['notice']);
        $this->assertNull(Field::text('name')->toArray()['notice']);
    }
}
