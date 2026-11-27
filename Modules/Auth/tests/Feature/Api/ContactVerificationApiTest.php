<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Support\Masks;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * E-mail et téléphone masqués dans « Changer d'adresse e-mail » et « Changer de numéro », et un lien pour vérifier la
 * valeur actuelle quand elle ne l'est pas (décisions 2026-11-27-email-telephone-masques,
 * 2026-11-27-verifier-email-telephone et 2026-11-27-formulaire-liens).
 */
class ContactVerificationApiTest extends TestCase
{
    use CapturesVerificationCodes;
    use RefreshDatabase;

    private const PHONE = '+41791234567';

    private Country $switzerland;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureVerificationCodes();
        $this->switzerland = Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
    }

    private function asUser(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('iPhone')->plainTextToken)->withHeader('Accept-Language', 'fr');
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(['locale' => 'fr', 'phone' => self::PHONE, 'phone_country_id' => $this->switzerland->getKey(), ...$attributes]);
    }

    public function test_the_profile_serves_the_masked_email_and_phone(): void
    {
        $user = $this->member(['email_verified_at' => null]);

        $this->asUser($user)->getJson('/api/v1/auth/me')->assertOk()
            ->assertJsonPath('data.email_masked', Masks::email($user->email))
            ->assertJsonPath('data.phone_masked', '+41 ••• 67')
            ->assertJsonPath('data.email_verified', false)
            ->assertJsonPath('data.phone_verified', false);
        $this->assertStringContainsString('•••@', Masks::email($user->email));
    }

    public function test_an_unverified_email_shows_a_link_to_verify_it(): void
    {
        $user = $this->member(['email_verified_at' => null]);

        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.email')->assertOk()
            ->assertJsonPath('data.fields.0.default', Masks::email($user->email))
            ->assertJsonPath('data.fields.0.help', "Cette adresse n'est pas encore vérifiée.")
            ->assertJsonPath('data.links', [['label' => 'Vérifier mon adresse', 'form' => 'auth.profile.email.verify']]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.email')->assertOk()
            ->assertJsonPath('data.fields.0.help', null)
            ->assertJsonPath('data.links', []);
    }

    public function test_an_unverified_phone_shows_a_link_to_resend_the_code(): void
    {
        $user = $this->member();

        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.phone')->assertOk()
            ->assertJsonPath('data.fields.0.default', '+41 ••• 67')
            ->assertJsonPath('data.links', [['label' => 'Renvoyer le code', 'form' => 'auth.profile.phone.verify']]);

        $user->forceFill(['phone_verified_at' => now()])->save();
        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.phone')->assertOk()->assertJsonPath('data.links', []);
    }

    public function test_the_verify_form_shows_the_masked_email_and_sends_a_code_that_verifies_it(): void
    {
        $user = $this->member(['email_verified_at' => null]);

        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.email.verify')->assertOk()
            ->assertJsonPath('data.title', 'Vérifier mon adresse e-mail')
            ->assertJsonPath('data.fields.0.default', Masks::email($user->email))
            ->assertJsonPath('data.fields.0.readonly', true)
            ->assertJsonPath('data.submit.label', 'Envoyer le code')
            ->assertJsonPath('data.submit.endpoint', '/v1/auth/me/email/verify');

        $challenge = $this->asUser($user)->postJson('/api/v1/auth/me/email/verify')->assertAccepted()
            ->assertJsonPath('channel', 'email')
            ->assertJsonPath('destination', Masks::email($user->email))
            ->json('challenge');

        $this->asUser($user)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode($user->email)])
            ->assertOk()->assertJsonPath('data.email_verified', true);
        $this->assertNotNull($user->fresh()->email_verified_at);

        // Déjà vérifiée : plus rien à envoyer.
        $this->asUser($user)->postJson('/api/v1/auth/me/email/verify')->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_the_phone_is_verified_by_a_code_sent_by_sms(): void
    {
        $user = $this->member();

        $challenge = $this->asUser($user)->postJson('/api/v1/auth/me/phone/verify')->assertAccepted()
            ->assertJsonPath('channel', 'sms')
            ->assertJsonPath('destination', '+41 ••• 67')
            ->json('challenge');

        $this->asUser($user)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode(self::PHONE)])
            ->assertOk()->assertJsonPath('data.phone_verified', true);
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_only_the_account_itself_validates_its_code(): void
    {
        $user = $this->member(['email_verified_at' => null]);
        $challenge = $this->asUser($user)->postJson('/api/v1/auth/me/email/verify')->json('challenge');

        $this->asUser(User::factory()->create())->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode($user->email)])
            ->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_an_account_without_phone_cannot_open_the_phone_verify_form(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->asUser($user)->getJson('/api/v1/forms/auth.profile.phone.verify')->assertForbidden();
        $this->asUser($user)->postJson('/api/v1/auth/me/phone/verify')->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->postJson('/api/v1/auth/me/phone/verify')->assertUnauthorized();
    }

    public function test_signing_in_with_an_email_code_verifies_the_email(): void
    {
        $user = $this->member(['email_verified_at' => null]);

        $challenge = $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => $user->email])->assertAccepted()->json('challenge');
        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode($user->email)])->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
