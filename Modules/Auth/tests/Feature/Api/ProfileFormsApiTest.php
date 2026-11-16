<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * Les trois formulaires du profil de l'app mobile : informations personnelles,
 * mot de passe, préférences — chacun avec sa route, son contrôleur et son service.
 */
class ProfileFormsApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create(['locale' => 'fr', 'first_name' => 'Jeanne', 'last_name' => 'Dupont', ...$attributes]);
    }

    private function asUser(User $user): static
    {
        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    // Informations personnelles

    public function test_personal_info_form_offers_first_and_last_name_with_current_values(): void
    {
        $user = $this->user();

        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.personal')
            ->assertOk()
            ->assertJsonPath('data.title', 'Informations personnelles')
            ->assertJsonPath('data.fields.0.name', 'first_name')
            ->assertJsonPath('data.fields.0.default', 'Jeanne')
            ->assertJsonPath('data.fields.1.name', 'last_name')
            ->assertJsonPath('data.fields.1.default', 'Dupont')
            ->assertJsonCount(2, 'data.fields');
    }

    public function test_personal_info_updates_first_and_last_name_only(): void
    {
        $user = $this->user(['name' => 'jeanne', 'email' => 'jeanne@example.com']);

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [
            'first_name' => 'Jeanne-Marie',
            'last_name' => 'Durand',
            'email' => 'pirate@example.com',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Jeanne-Marie')
            ->assertJsonPath('data.last_name', 'Durand');

        $user->refresh();
        $this->assertSame(['Jeanne-Marie', 'Durand', 'jeanne@example.com', 'fr'], [$user->first_name, $user->last_name, $user->email, $user->locale]);
    }

    public function test_personal_info_refuses_an_empty_name_with_a_translated_message(): void
    {
        $user = $this->user();

        $response = $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', ['first_name' => '', 'last_name' => 'Durand'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name']);

        $this->assertSame('Le champ Prénom est obligatoire.', $response->json('errors.first_name.0'));
        $this->assertSame('Jeanne', $user->fresh()->first_name);
    }

    // Mot de passe

    public function test_password_form_asks_for_current_new_and_confirmation(): void
    {
        $this->asUser($this->user())->getJson('/api/v1/forms/auth.profile.password')
            ->assertOk()
            ->assertJsonPath('data.title', 'Changer le mot de passe')
            ->assertJsonPath('data.fields.*.name', ['current_password', 'password', 'password_confirmation'])
            ->assertJsonPath('data.fields.*.type', ['password', 'password', 'password']);
    }

    public function test_changing_the_password_keeps_this_device_and_signs_out_the_others(): void
    {
        $user = $this->user(['password' => 'ancien-secret']);
        $other = $user->createToken('iPad')->plainTextToken;
        $current = $user->createToken('iPhone')->plainTextToken;

        $this->withToken($current)->putJson('/api/v1/auth/me/password', [
            'current_password' => 'ancien-secret',
            'password' => 'nouveau-secret-2026',
            'password_confirmation' => 'nouveau-secret-2026',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('nouveau-secret-2026', $user->fresh()->password));
        $this->assertSame(['iPhone'], $user->tokens()->pluck('name')->all());

        $this->app['auth']->forgetGuards();
        $this->withToken($current)->getJson('/api/v1/auth/me')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($other)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_a_wrong_current_password_is_refused_under_its_field(): void
    {
        $user = $this->user(['password' => 'ancien-secret']);

        $response = $this->asUser($user)->putJson('/api/v1/auth/me/password', [
            'current_password' => 'pas-le-bon',
            'password' => 'nouveau-secret-2026',
            'password_confirmation' => 'nouveau-secret-2026',
        ])->assertUnprocessable();

        $this->assertSame(['current_password'], array_keys($response->json('errors')));
        $this->assertTrue(Hash::check('ancien-secret', $user->fresh()->password));
    }

    public function test_a_short_or_unconfirmed_new_password_is_refused(): void
    {
        $user = $this->user(['password' => 'ancien-secret']);

        $this->asUser($user)->putJson('/api/v1/auth/me/password', [
            'current_password' => 'ancien-secret',
            'password' => 'court',
            'password_confirmation' => 'court',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->asUser($user)->putJson('/api/v1/auth/me/password', [
            'current_password' => 'ancien-secret',
            'password' => 'nouveau-secret-2026',
            'password_confirmation' => 'autre-chose-2026',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->assertTrue(Hash::check('ancien-secret', $user->fresh()->password));
    }

    public function test_the_new_password_must_differ_from_the_current_one(): void
    {
        $user = $this->user(['password' => 'ancien-secret']);

        $this->asUser($user)->putJson('/api/v1/auth/me/password', [
            'current_password' => 'ancien-secret',
            'password' => 'ancien-secret',
            'password_confirmation' => 'ancien-secret',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    // Préférences

    public function test_preferences_change_the_locale(): void
    {
        $user = $this->user();

        $this->asUser($user)->patchJson('/api/v1/auth/me/preferences', ['locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.locale', 'en');

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_preferences_refuse_an_unavailable_locale_and_ignore_other_fields(): void
    {
        $user = $this->user(['name' => 'jeanne']);

        $this->asUser($user)->patchJson('/api/v1/auth/me/preferences', ['locale' => 'de'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['locale']);

        $this->asUser($user)->patchJson('/api/v1/auth/me/preferences', ['locale' => 'en', 'name' => 'pirate', 'roles' => ['super_admin']])
            ->assertOk();

        $user->refresh();
        $this->assertSame(['en', 'jeanne'], [$user->locale, $user->name]);
        $this->assertCount(0, $user->roles);
    }

    // Communs

    public function test_every_profile_route_requires_a_token(): void
    {
        $this->patchJson('/api/v1/auth/me/personal-info', ['first_name' => 'X', 'last_name' => 'Y'])->assertUnauthorized();
        $this->putJson('/api/v1/auth/me/password', [])->assertUnauthorized();
        $this->patchJson('/api/v1/auth/me/preferences', ['locale' => 'en'])->assertUnauthorized();
    }

    public function test_the_former_update_route_and_locale_form_are_gone(): void
    {
        $user = $this->user();

        $this->asUser($user)->patchJson('/api/v1/auth/me', ['locale' => 'en'])->assertMethodNotAllowed();
        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.locale')->assertNotFound();
        $this->assertSame('fr', $user->fresh()->locale);
    }
}
