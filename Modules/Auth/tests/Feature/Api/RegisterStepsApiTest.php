<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Formulaire à étapes (module Form), sur l'exemple de l'inscription : chaque étape
 * est validée par `POST /api/v1/forms/{form}/steps/{n}` avant la suivante.
 */
class RegisterStepsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
    }

    private function step(int $number, array $data = [], string $form = 'auth.register')
    {
        return $this->postJson("/api/v1/forms/{$form}/steps/{$number}", $data);
    }

    public function test_the_register_form_has_three_steps_with_gender_after_the_last_name(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.register')->assertOk();

        $this->assertSame(['name', 'first_name', 'last_name', 'gender', 'email', 'phone', 'password', 'password_confirmation'], array_column($response->json('data.fields'), 'name'));
        $response->assertJsonPath('data.fields.3.type', 'select')
            ->assertJsonPath('data.fields.3.required', true)
            ->assertJsonPath('data.steps', [
                ['title' => 'Vous', 'fields' => ['name', 'first_name', 'last_name', 'gender']],
                ['title' => 'Vous joindre', 'fields' => ['email', 'phone']],
                ['title' => 'Mot de passe', 'fields' => ['password', 'password_confirmation']],
            ]);
    }

    public function test_a_form_without_steps_sends_an_empty_list(): void
    {
        $this->getJson('/api/v1/forms/auth.login')->assertOk()->assertJsonPath('data.steps', []);
    }

    public function test_each_step_checks_only_its_own_fields(): void
    {
        $this->step(1)->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'first_name', 'last_name', 'gender'])
            ->assertJsonMissingValidationErrors(['email', 'phone', 'password']);

        $this->step(2)->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone'])
            ->assertJsonMissingValidationErrors(['name', 'password']);

        $this->step(3, ['password' => 'Secret-password-1', 'password_confirmation' => 'other'])->assertUnprocessable()
            ->assertJsonValidationErrors(['password'])
            ->assertJsonMissingValidationErrors(['name', 'email']);
    }

    public function test_a_valid_step_answers_204_and_saves_nothing(): void
    {
        $this->step(1, ['name' => 'marie', 'first_name' => 'Marie', 'last_name' => 'Curie', 'gender' => 'female'])->assertNoContent();
        $this->step(2, ['phone' => '079 123 45 67', 'phone_country' => 'CH'])->assertNoContent();
        $this->step(3, ['password' => 'Secret-password-1', 'password_confirmation' => 'Secret-password-1'])->assertNoContent();

        $this->assertSame(0, User::count());
    }

    public function test_uniqueness_is_checked_at_the_step_of_the_field(): void
    {
        User::factory()->create(['name' => 'marie', 'email' => 'marie@example.com', 'phone' => '+41791234567']);

        $this->step(1, ['name' => 'marie', 'first_name' => 'Marie', 'last_name' => 'Curie', 'gender' => 'female'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name']);
        $this->step(2, ['email' => 'marie@example.com', 'phone' => '079 123 45 67', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_an_unknown_step_or_a_form_without_steps_answers_404(): void
    {
        $this->step(4)->assertNotFound();
        $this->step(1, [], 'auth.login')->assertNotFound();
        $this->step(1, [], 'nope')->assertNotFound();
    }

    public function test_a_private_form_step_needs_an_account(): void
    {
        $this->step(1, [], 'auth.profile.personal')->assertUnauthorized();
    }
}
