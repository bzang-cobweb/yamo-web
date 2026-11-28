<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Formulaire d'inscription (module Form) : une seule page, prénom, nom, puis l'autre moyen de contact, facultatif,
 * selon le canal de l'identifiant prouvé (décision 2026-11-28-connexion-panneau). L'envoi valide tout d'un coup.
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

    public function test_the_register_form_asks_the_names_then_the_other_contact(): void
    {
        $byEmail = $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.register?channel=email')->assertOk();
        $this->assertSame(['first_name', 'last_name', 'phone'], array_column($byEmail->json('data.fields'), 'name'));
        $byEmail->assertJsonPath('data.steps', [])
            ->assertJsonPath('data.title', 'Créer votre compte')
            ->assertJsonPath('data.submit.label', 'Créer mon compte')
            ->assertJsonPath('data.fields.0.label', 'Prénom')
            ->assertJsonPath('data.fields.1.label', 'Nom')
            ->assertJsonPath('data.fields.2.type', 'phone')
            ->assertJsonPath('data.fields.2.required', false)
            ->assertJsonPath('data.fields.2.default', ['country' => 'CH', 'number' => null]);

        $byPhone = $this->getJson('/api/v1/forms/auth.register?channel=phone')->assertOk();
        $this->assertSame(['first_name', 'last_name', 'email'], array_column($byPhone->json('data.fields'), 'name'));
        $byPhone->assertJsonPath('data.fields.2.type', 'email')->assertJsonPath('data.fields.2.required', false);

        // `sms` (le canal d'un défi) vaut `phone` ; sans canal, ni l'un ni l'autre.
        $this->assertSame(['first_name', 'last_name', 'email'], array_column($this->getJson('/api/v1/forms/auth.register?channel=sms')->json('data.fields'), 'name'));
        $this->assertSame(['first_name', 'last_name'], array_column($this->getJson('/api/v1/forms/auth.register')->json('data.fields'), 'name'));
    }

    public function test_a_form_without_steps_sends_an_empty_list(): void
    {
        $this->getJson('/api/v1/forms/auth.login')->assertOk()->assertJsonPath('data.steps', []);
    }

    public function test_the_submission_checks_the_fields_and_the_challenge(): void
    {
        $this->postJson('/api/v1/auth/register', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['challenge', 'first_name', 'last_name'])
            ->assertJsonMissingValidationErrors(['name', 'gender', 'password', 'birth_date', 'email', 'phone']);

        $this->assertSame(0, User::count());
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
