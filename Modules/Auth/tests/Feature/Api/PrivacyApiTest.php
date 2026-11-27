<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Mail\PersonalDataMail;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * « Confidentialité » : visibilité du profil, lieu sous ses avis, demander ses données (décision
 * 2026-11-27-confidentialite).
 */
class PrivacyApiTest extends TestCase
{
    use RefreshDatabase;

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    public function test_the_visibility_form_offers_four_exclusive_choices_anyone_by_default(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $field = $this->asUser($user)->getJson('/api/v1/forms/auth.profile.privacy.visibility')->assertOk()
            ->assertJsonPath('data.title', 'Visibilité du profil')
            ->assertJsonPath('data.submit.endpoint', '/v1/auth/me/privacy/visibility')
            ->json('data.fields.0');

        $this->assertSame('radio', $field['type']);
        $this->assertSame('profile_visibility', $field['name']);
        $this->assertSame('anyone', $field['default']);
        $this->assertSame(['anyone', 'owners', 'connections', 'owners_and_connections'], array_column($field['options'], 'value'));
        $this->assertSame('Tout le monde sur yamo', $field['options'][0]['label']);
    }

    public function test_the_visibility_is_saved_and_shown_again(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->asUser($user)->patchJson('/api/v1/auth/me/privacy/visibility', ['profile_visibility' => 'connections'])->assertOk();
        $this->assertSame('connections', $user->fresh()->privacySettings()['profile_visibility']);
        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.privacy.visibility')->assertJsonPath('data.fields.0.default', 'connections');

        $this->asUser($user)->patchJson('/api/v1/auth/me/privacy/visibility', ['profile_visibility' => 'nobody'])
            ->assertUnprocessable()->assertJsonValidationErrors('profile_visibility');
        // Le lieu sous les avis ne bouge pas.
        $this->assertFalse($user->fresh()->privacySettings()['review_location']);
    }

    public function test_the_review_location_is_a_switch_off_by_default(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.privacy.reviews')->assertOk()
            ->assertJsonPath('data.title', 'Avis')
            ->assertJsonPath('data.fields.0.type', 'switch')
            ->assertJsonPath('data.fields.0.label', 'Montrer mon pays, ma région et ma ville')
            ->assertJsonPath('data.fields.0.default', false);

        $this->asUser($user)->patchJson('/api/v1/auth/me/privacy/reviews', ['review_location' => true])->assertOk();
        $this->assertTrue($user->fresh()->privacySettings()['review_location']);
        $this->assertSame('anyone', $user->fresh()->privacySettings()['profile_visibility']);
        $this->asUser($user)->patchJson('/api/v1/auth/me/privacy/reviews', ['review_location' => false])->assertOk();
        $this->assertFalse($user->fresh()->privacySettings()['review_location']);
    }

    public function test_personal_data_are_sent_as_json_to_the_verified_email(): void
    {
        Mail::fake();
        $user = User::factory()->create(['locale' => 'fr', 'first_name' => 'Awa', 'email_verified_at' => now()]);

        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.privacy.data')->assertOk()
            ->assertJsonPath('data.title', 'Mes données')
            ->assertJsonPath('data.fields', [])
            ->assertJsonPath('data.submit.label', 'Demander mes données');

        $this->asUser($user)->postJson('/api/v1/auth/me/personal-data')->assertNoContent();

        Mail::assertSent(PersonalDataMail::class, function (PersonalDataMail $mail) use ($user): bool {
            $data = json_decode($mail->json, true);

            return $mail->hasTo($user->email)
                && $data['account']['first_name'] === 'Awa'
                && $data['account']['email'] === $user->email
                && ! array_key_exists('password', $data['account'])
                && $data['privacy_settings']['profile_visibility'] === 'anyone'
                && count($mail->attachments()) === 1;
        });
    }

    public function test_personal_data_are_refused_without_a_verified_email(): void
    {
        Mail::fake();
        $user = User::factory()->create(['locale' => 'fr', 'email_verified_at' => null]);

        $this->asUser($user)->postJson('/api/v1/auth/me/personal-data')->assertUnprocessable()
            ->assertJsonPath('errors.email.0', "Vérifiez d'abord votre adresse e-mail : vos données ne sont envoyées qu'à une adresse vérifiée.");
        Mail::assertNothingSent();
    }

    public function test_a_guest_reaches_none_of_it(): void
    {
        $this->patchJson('/api/v1/auth/me/privacy/visibility', ['profile_visibility' => 'owners'])->assertUnauthorized();
        $this->patchJson('/api/v1/auth/me/privacy/reviews', ['review_location' => true])->assertUnauthorized();
        $this->postJson('/api/v1/auth/me/personal-data')->assertUnauthorized();
    }
}
