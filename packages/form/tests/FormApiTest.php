<?php

namespace Bza\Form\Tests;

use Bza\Form\Tests\Fixtures\ContactRequest;
use Illuminate\Support\Facades\Route;

/**
 * A form is described, validated step by step and received through the package's API.
 */
class FormApiTest extends TestCase
{
    public function test_a_form_is_described_with_its_fields_steps_and_submission(): void
    {
        $this->getJson($this->packageRoute('v1.forms.show', ['form' => 'test.contact']))
            ->assertOk()
            ->assertJsonPath('data.name', 'test.contact')
            ->assertJsonPath('data.title', 'Contact us')
            ->assertJsonPath('data.title_align', 'start')
            ->assertJsonPath('data.fields.0.name', 'name')
            ->assertJsonPath('data.fields.0.required', true)
            ->assertJsonPath('data.fields.2.options', [
                ['value' => 'sales', 'label' => 'Sales', 'hint' => null, 'parent' => null],
                ['value' => 'support', 'label' => 'Support', 'hint' => null, 'parent' => null],
            ])
            ->assertJsonPath('data.fields.3.upload.max_files', 2)
            ->assertJsonPath('data.fields.3.upload.endpoint', '/v1/forms/test.contact/uploads/photos')
            ->assertJsonPath('data.steps.1', ['title' => 'Your request', 'subtitle' => 'Tell us more', 'fields' => ['topic', 'photos']])
            ->assertJsonPath('data.submit', [
                'label' => 'Send',
                'method' => 'GET',
                'endpoint' => '/v1/forms/test.contact',
                'destructive' => false,
                'success' => ['action' => 'back', 'message' => 'Sent'],
            ]);
    }

    public function test_a_record_prefills_the_form(): void
    {
        $url = $this->packageRoute('v1.forms.show', ['form' => 'test.contact']);

        $this->getJson($url.'?record=7')->assertOk()->assertJsonPath('data.fields.0.default', 'record-7');
        $this->getJson($url.'?record='.str_repeat('x', 65))->assertUnprocessable()->assertJsonValidationErrors('record');
    }

    public function test_unknown_and_unavailable_forms_are_not_found(): void
    {
        $this->getJson($this->packageRoute('v1.forms.show', ['form' => 'test.missing']))->assertNotFound();
        $this->getJson($this->packageRoute('v1.forms.show', ['form' => 'test.unavailable']))->assertNotFound();
    }

    public function test_a_restricted_form_needs_an_allowed_account(): void
    {
        $url = $this->packageRoute('v1.forms.show', ['form' => 'test.private']);

        $this->getJson($url)->assertUnauthorized();

        $this->signIn('someone@example.test');
        $this->getJson($url)->assertForbidden();

        $this->signIn('someone@allowed.test');
        $this->getJson($url)->assertOk()->assertJsonPath('data.fields.0.name', 'note');
    }

    public function test_a_step_is_validated_alone(): void
    {
        $step = fn (int $number): string => $this->packageRoute('v1.forms.steps.validate', ['form' => 'test.contact', 'step' => $number]);

        $this->postJson($step(1), ['name' => 'Ada', 'email' => 'ada@example.test'])->assertNoContent();
        // Step 2 does not check step 1's fields.
        $this->postJson($step(2), ['topic' => 'sales'])->assertNoContent();
        $this->postJson($step(1), ['name' => 'Ada', 'email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonMissingValidationErrors('name');
        $this->postJson($step(2), ['topic' => 'unknown'])->assertUnprocessable()->assertJsonValidationErrors('topic');
        $this->postJson($step(3), [])->assertNotFound();
    }

    public function test_a_form_backed_request_validates_with_the_form_rules(): void
    {
        Route::post('form-test/contact', fn (ContactRequest $request): array => $request->validated());

        $this->postJson('form-test/contact', ['name' => 'Ada', 'email' => 'ada@example.test', 'topic' => 'support', 'extra' => 'ignored'])
            ->assertOk()
            ->assertExactJson(['name' => 'Ada', 'email' => 'ada@example.test', 'topic' => 'support']);

        // The field labels name the fields in the messages.
        $this->postJson('form-test/contact', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email'])
            ->assertJsonFragment(['email' => [trans('validation.required', ['attribute' => 'E-mail'])]]);
    }
}
