<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Formulaire d'inscription (module Form) : une seule page, sans étapes, sans genre
 * ni mot de passe ; l'identifiant saisi à la connexion revient en champs cachés
 * (décision 2026-11-27-connexion-sans-mot-de-passe). L'envoi valide tout d'un coup.
 */
class RegisterFormApiTest extends TestCase
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

    public function test_the_register_form_is_a_single_page_without_gender_or_password(): void
    {
        $response = $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.register')->assertOk();

        $this->assertSame(['name', 'first_name', 'last_name', 'birth_date', 'email', 'phone', 'phone_country'], array_column($response->json('data.fields'), 'name'));
        $response->assertJsonPath('data.steps', [])
            ->assertJsonPath('data.fields.4.type', 'hidden')
            ->assertJsonPath('data.fields.5.type', 'hidden')
            ->assertJsonPath('data.fields.6.type', 'hidden');
    }

    public function test_a_form_without_steps_sends_an_empty_list(): void
    {
        $this->getJson('/api/v1/forms/auth.login')->assertOk()->assertJsonPath('data.steps', []);
    }

    public function test_the_submission_checks_every_field_at_once(): void
    {
        $this->postJson('/api/v1/auth/register', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'first_name', 'last_name', 'email', 'phone'])
            ->assertJsonMissingValidationErrors(['gender', 'password', 'password_confirmation', 'description']);

        // Un numéro sans son pays.
        $this->postJson('/api/v1/auth/register', ['name' => 'marie', 'first_name' => 'Marie', 'last_name' => 'Curie', 'phone' => '079 123 45 67'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_country'])
            ->assertJsonMissingValidationErrors(['email']);

        $this->assertSame(0, User::count());
    }

    public function test_uniqueness_is_checked_on_submission(): void
    {
        User::factory()->create(['name' => 'marie', 'email' => 'marie@example.com', 'phone' => '+41791234567']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'marie', 'first_name' => 'Marie', 'last_name' => 'Curie',
            'email' => 'marie@example.com', 'phone' => '079 123 45 67', 'phone_country' => 'CH',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'phone']);

        $this->assertSame(1, User::count());
    }

    public function test_an_unknown_step_or_a_form_without_steps_answers_404(): void
    {
        $this->step(1)->assertNotFound();
        $this->step(1, [], 'auth.login')->assertNotFound();
        $this->step(1, [], 'nope')->assertNotFound();
    }

    public function test_a_private_form_step_needs_an_account(): void
    {
        $this->step(1, [], 'auth.profile.personal')->assertUnauthorized();
    }
}
