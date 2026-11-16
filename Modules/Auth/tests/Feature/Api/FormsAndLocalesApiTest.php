<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Forms\LoginForm;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * Formulaires servis à l'app mobile (`GET /api/v1/forms/{form}`) et langues
 * disponibles (`GET /api/v1/locales`). Les formulaires du profil sont testés dans
 * `ProfileFormsApiTest`.
 */
class FormsAndLocalesApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_login_form_is_public_and_translated(): void
    {
        $fr = $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.login')->assertOk();

        $fr->assertExactJson(['data' => [
            'name' => 'auth.login',
            'title' => 'Se connecter',
            'subtitle' => 'Accédez à votre compte yamo.',
            'fields' => [
                [
                    'name' => 'email', 'type' => 'email', 'label' => 'Adresse e-mail', 'placeholder' => null,
                    'help' => null, 'required' => true, 'readonly' => false, 'depends_on' => null, 'default' => null, 'options' => [],
                ],
                [
                    'name' => 'password', 'type' => 'password', 'label' => 'Mot de passe', 'placeholder' => null,
                    'help' => null, 'required' => true, 'readonly' => false, 'depends_on' => null, 'default' => null, 'options' => [],
                ],
            ],
            'submit' => ['label' => 'Se connecter'],
        ]]);

        $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/forms/auth.login')
            ->assertOk()
            ->assertJsonPath('data.title', 'Sign in')
            ->assertJsonPath('data.subtitle', 'Access your yamo account.')
            ->assertJsonPath('data.fields.0.label', 'Email address')
            ->assertJsonPath('data.submit.label', 'Sign in');
    }

    public function test_a_form_without_subtitle_sends_null_and_a_field_can_carry_help(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->withToken($this->tokenFor($user))->getJson('/api/v1/forms/auth.profile.preferences')
            ->assertOk()
            ->assertJsonPath('data.title', 'Préférences')
            ->assertJsonPath('data.subtitle', null)
            ->assertJsonPath('data.fields.0.help', "Langue de l'application et des e-mails.");
    }

    public function test_a_guest_asking_for_a_private_form_gets_a_translated_401(): void
    {
        $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.profile.preferences')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Vous devez vous connecter.']);
    }

    public function test_login_validation_comes_from_the_login_form(): void
    {
        $rules = (new LoginRequest)->rules();

        $this->assertSame((new LoginForm)->rules(), array_diff_key($rules, ['device_name' => true]));
        $this->assertArrayHasKey('device_name', $rules);
        $this->assertNotContains('device_name', array_column((new LoginForm)->fields(), 'name'));
    }

    public function test_unknown_form_returns_404(): void
    {
        $this->getJson('/api/v1/forms/nope')->assertNotFound();
    }

    public function test_preferences_form_requires_a_token(): void
    {
        $this->getJson('/api/v1/forms/auth.profile.preferences')->assertUnauthorized();
    }

    public function test_preferences_form_offers_available_locales_and_current_value(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->withToken($this->tokenFor($user))
            ->withHeader('Accept-Language', 'fr')
            ->getJson('/api/v1/forms/auth.profile.preferences')
            ->assertOk()
            ->assertJsonPath('data.fields.0.name', 'locale')
            ->assertJsonPath('data.fields.0.type', 'select')
            ->assertJsonPath('data.fields.0.default', 'en')
            ->assertJsonPath('data.fields.0.options', [
                ['value' => 'fr', 'label' => 'Français', 'hint' => null, 'parent' => null],
                ['value' => 'en', 'label' => 'English', 'hint' => null, 'parent' => null],
            ])
            // Compte connecté : la langue du compte l'emporte sur l'en-tête.
            ->assertJsonPath('data.fields.0.label', 'Language')
            ->assertJsonPath('data.submit.label', 'Save');
    }

    public function test_locales_are_public(): void
    {
        $this->getJson('/api/v1/locales')
            ->assertOk()
            ->assertExactJson([
                'data' => [['code' => 'fr', 'label' => 'Français'], ['code' => 'en', 'label' => 'English']],
                'default' => 'fr',
            ]);
    }
}
