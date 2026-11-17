<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;
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

    public function test_personal_info_form_offers_the_four_fields_with_current_values(): void
    {
        $user = $this->user(['gender' => 'female', 'birth_date' => '1990-04-12']);

        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.personal')
            ->assertOk()
            ->assertJsonPath('data.title', 'Informations personnelles')
            ->assertJsonPath('data.fields.*.name', ['first_name', 'last_name', 'gender', 'birth_date', 'country_id', 'region_id', 'description'])
            ->assertJsonPath('data.fields.0.default', 'Jeanne')
            ->assertJsonPath('data.fields.1.default', 'Dupont')
            // Genre : liste déroulante, obligatoire, options de GenderEnum traduites.
            ->assertJsonPath('data.fields.2.type', 'select')
            ->assertJsonPath('data.fields.2.required', true)
            ->assertJsonPath('data.fields.2.default', 'female')
            ->assertJsonPath('data.fields.2.options', [['value' => 'male', 'label' => 'Homme', 'hint' => null, 'parent' => null], ['value' => 'female', 'label' => 'Femme', 'hint' => null, 'parent' => null]])
            // Date de naissance : type date, facultative.
            ->assertJsonPath('data.fields.3.type', 'date')
            ->assertJsonPath('data.fields.3.required', false)
            ->assertJsonPath('data.fields.3.default', '1990-04-12');
    }

    public function test_personal_info_updates_its_four_fields_only(): void
    {
        $user = $this->user(['name' => 'jeanne', 'email' => 'jeanne@example.com']);

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [
            'first_name' => 'Élodie',
            'last_name' => 'Ängel',
            'gender' => 'female',
            'birth_date' => '1990-04-12',
            'email' => 'pirate@example.com',
            'locale' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Élodie')
            ->assertJsonPath('data.full_name', 'Élodie Ängel')
            ->assertJsonPath('data.initials', 'EA')
            ->assertJsonPath('data.gender', 'female')
            ->assertJsonPath('data.birth_date', '1990-04-12');

        $user->refresh();
        $this->assertSame(['Élodie', 'Ängel', 'jeanne@example.com', 'fr'], [$user->first_name, $user->last_name, $user->email, $user->locale]);
        $this->assertSame('1990-04-12', $user->birth_date->format('Y-m-d'));
    }

    public function test_personal_info_saves_clears_and_limits_the_description(): void
    {
        $user = $this->user(['gender' => 'female']);
        $base = ['first_name' => 'Jeanne', 'last_name' => 'Dupont', 'gender' => 'female'];

        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.personal')
            ->assertJsonPath('data.fields.6.type', 'textarea')
            ->assertJsonPath('data.fields.6.required', false)
            ->assertJsonPath('data.fields.6.max_length', 1000);

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$base, 'description' => "Ligne 1\nLigne 2"])
            ->assertOk()
            ->assertJsonPath('data.description', "Ligne 1\nLigne 2");
        $this->asUser($user)->getJson('/api/v1/auth/me')->assertJsonPath('data.description', "Ligne 1\nLigne 2");

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$base, 'description' => str_repeat('a', 1001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('description');

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$base, 'description' => ''])->assertOk();
        $this->assertNull($user->refresh()->description);
    }

    public function test_personal_info_refuses_an_empty_name_with_a_translated_message(): void
    {
        $user = $this->user();

        $response = $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', ['first_name' => '', 'last_name' => 'Durand', 'gender' => 'female'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name']);

        $this->assertSame('Le champ Prénom est obligatoire.', $response->json('errors.first_name.0'));
        $this->assertSame('Jeanne', $user->fresh()->first_name);
    }

    public function test_gender_is_required_and_must_be_male_or_female(): void
    {
        $user = $this->user(['gender' => null]);

        $missing = $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', ['first_name' => 'Jeanne', 'last_name' => 'Dupont'])
            ->assertUnprocessable();
        $this->assertSame('Le champ Genre est obligatoire.', $missing->json('errors.gender.0'));

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', ['first_name' => 'Jeanne', 'last_name' => 'Dupont', 'gender' => 'other'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);

        $this->assertNull($user->fresh()->gender);
    }

    public function test_birth_date_is_optional_and_an_empty_value_clears_it(): void
    {
        $user = $this->user(['gender' => 'male', 'birth_date' => '1990-04-12']);

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [
            'first_name' => 'Jean', 'last_name' => 'Dupont', 'gender' => 'male', 'birth_date' => '',
        ])->assertOk()->assertJsonPath('data.birth_date', null);

        $this->assertNull($user->fresh()->birth_date);
    }

    public function test_birth_date_must_be_between_1900_and_today_with_translated_messages(): void
    {
        $user = $this->user(['gender' => 'male']);
        $base = ['first_name' => 'Jean', 'last_name' => 'Dupont', 'gender' => 'male'];

        $future = $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$base, 'birth_date' => now()->addDay()->format('Y-m-d')])
            ->assertUnprocessable();
        $this->assertStringContainsString("aujourd'hui", $future->json('errors.birth_date.0'));

        $old = $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$base, 'birth_date' => '1899-12-31'])
            ->assertUnprocessable();
        $this->assertStringContainsString('1er janvier 1900', $old->json('errors.birth_date.0'));

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$base, 'birth_date' => now()->format('Y-m-d')])->assertOk();
        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$base, 'birth_date' => '31.12.1990'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['birth_date']);
    }

    public function test_country_and_region_are_saved_and_the_region_must_belong_to_the_country(): void
    {
        $user = $this->user(['gender' => 'female']);
        $switzerland = Country::factory()->create(['code' => 'CH']);
        $france = Country::factory()->create(['code' => 'FR']);
        $geneva = Region::factory()->for($switzerland)->create();
        $brittany = Region::factory()->for($france)->create();
        $base = ['first_name' => 'Jeanne', 'last_name' => 'Dupont', 'gender' => 'female'];

        $form = $this->asUser($user)->getJson('/api/v1/forms/auth.profile.personal')->assertOk();
        $region = collect($form->json('data.fields'))->firstWhere('name', 'region_id');
        $this->assertSame('country_id', $region['depends_on']);
        $this->assertContains(['value' => (string) $geneva->getKey(), 'label' => (string) $geneva->name, 'hint' => null, 'parent' => (string) $switzerland->getKey()], $region['options']);

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$base, 'country_id' => (string) $switzerland->getKey(), 'region_id' => (string) $brittany->getKey()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['region_id']);

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [...$base, 'country_id' => (string) $switzerland->getKey(), 'region_id' => (string) $geneva->getKey()])
            ->assertOk()
            ->assertJsonPath('data.country.code', 'CH')
            ->assertJsonPath('data.region.id', $geneva->getKey());
    }

    public function test_regions_are_offered_in_the_back_office_order(): void
    {
        $switzerland = Country::factory()->create(['code' => 'CH']);
        $zurich = Region::factory()->for($switzerland)->create(['name' => ['fr' => 'Zurich', 'en' => 'Zurich'], 'position' => 1]);
        $argovie = Region::factory()->for($switzerland)->create(['name' => ['fr' => 'Argovie', 'en' => 'Aargau'], 'position' => 2]);

        $form = $this->asUser($this->user())->getJson('/api/v1/forms/auth.profile.personal')->assertOk();
        $region = collect($form->json('data.fields'))->firstWhere('name', 'region_id');

        $this->assertSame([(string) $zurich->getKey(), (string) $argovie->getKey()], array_column($region['options'], 'value'));
    }

    public function test_an_inactive_country_is_not_offered_nor_accepted(): void
    {
        $user = $this->user(['gender' => 'female']);
        $hidden = Country::factory()->create(['code' => 'XK', 'active' => false]);

        $form = $this->asUser($user)->getJson('/api/v1/forms/auth.profile.personal')->assertOk();
        $country = collect($form->json('data.fields'))->firstWhere('name', 'country_id');
        $this->assertNotContains((string) $hidden->getKey(), array_column($country['options'], 'value'));

        $this->asUser($user)->patchJson('/api/v1/auth/me/personal-info', [
            'first_name' => 'Jeanne', 'last_name' => 'Dupont', 'gender' => 'female', 'country_id' => (string) $hidden->getKey(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['country_id']);
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
