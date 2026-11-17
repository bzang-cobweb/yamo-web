<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Modules\Auth\Mail\VerificationCodeMail;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Mot de passe oublié : identifiant → code → nouveau mot de passe.
 */
class PasswordResetApiTest extends TestCase
{
    use CapturesVerificationCodes;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureVerificationCodes();
    }

    private function jeanne(array $attributes = []): User
    {
        return User::factory()->create(['email' => 'jeanne@example.com', 'password' => 'old-password', ...$attributes]);
    }

    private function forgot(string $identifier): TestResponse
    {
        // Un e-mail, ou un numéro suisse saisi au format national.
        $payload = str_contains($identifier, '@')
            ? ['channel' => 'email', 'email' => $identifier]
            : ['channel' => 'phone', 'phone' => $identifier, 'phone_country' => 'CH'];

        return $this->postJson('/api/v1/auth/password/forgot', $payload);
    }

    private function reset(string $challenge, string $password = 'New-password-42', ?string $confirmation = null): TestResponse
    {
        return $this->postJson('/api/v1/auth/password/reset', [
            'challenge' => $challenge,
            'password' => $password,
            'password_confirmation' => $confirmation ?? $password,
        ]);
    }

    public function test_the_whole_flow_changes_the_password_and_closes_every_session(): void
    {
        $user = $this->jeanne();
        $user->createToken('iPhone');
        $user->createToken('iPad');

        $challenge = $this->forgot('jeanne@example.com')
            ->assertAccepted()
            ->assertJsonPath('channel', 'email')
            ->assertJsonPath('destination', 'j•••@example.com')
            ->json('challenge');

        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode('jeanne@example.com')])
            ->assertOk()
            ->assertExactJson(['next' => 'password', 'challenge' => $challenge]);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));

        $this->reset($challenge)->assertNoContent();

        $user->refresh();
        $this->assertTrue(Hash::check('New-password-42', $user->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(0, VerificationCode::count());

        // On se reconnecte avec le nouveau mot de passe (puis un code).
        $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => 'jeanne@example.com', 'password' => 'New-password-42'])->assertAccepted();
    }

    public function test_an_unknown_identifier_gets_the_same_answer_and_nothing_is_sent(): void
    {
        $this->jeanne();

        $known = $this->forgot('jeanne@example.com')->assertAccepted()->json();
        $unknown = $this->forgot('nobody@example.com')->assertAccepted()->json();

        $this->assertSame(['challenge', 'channel', 'destination'], array_keys($unknown));
        $this->assertSame($known['channel'], $unknown['channel']);
        $this->assertSame('n•••@example.com', $unknown['destination']);
        Mail::assertSent(VerificationCodeMail::class, 1);

        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $unknown['challenge'], 'code' => '123456'])->assertUnprocessable();
        $this->reset($unknown['challenge'])->assertUnprocessable()->assertJsonValidationErrors(['code']);
    }

    public function test_a_verified_phone_receives_the_code_by_sms(): void
    {
        $switzerland = Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
        $this->jeanne(['phone' => '+41791234567', 'phone_country_id' => $switzerland->id, 'phone_verified_at' => now()]);

        $this->forgot('+41 79 123 45 67')->assertAccepted()->assertJsonPath('channel', 'sms')->assertJsonPath('destination', '+41 ••• 67');
        $this->assertNotEmpty($this->smsCode('+41791234567'));
        Mail::assertNothingSent();
    }

    public function test_the_password_does_not_change_without_a_validated_code(): void
    {
        $user = $this->jeanne();
        $challenge = $this->forgot('jeanne@example.com')->json('challenge');

        $this->reset($challenge)->assertUnprocessable()->assertJsonValidationErrors(['code']);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_a_code_validated_too_long_ago_no_longer_allows_the_change(): void
    {
        $user = $this->jeanne();
        $challenge = $this->forgot('jeanne@example.com')->json('challenge');
        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode('jeanne@example.com')])->assertOk();

        $this->travel(11)->minutes();

        $this->reset($challenge)->assertUnprocessable()->assertJsonValidationErrors(['code']);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_the_new_password_must_be_confirmed_and_strong_enough(): void
    {
        $this->jeanne();
        $challenge = $this->forgot('jeanne@example.com')->json('challenge');
        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode('jeanne@example.com')])->assertOk();

        $this->reset($challenge, 'New-password-42', 'other')->assertUnprocessable()->assertJsonValidationErrors(['password']);
        $this->reset($challenge, 'short')->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    public function test_both_forms_are_public_and_translated(): void
    {
        $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/forms/auth.password.forgot')->assertOk()
            ->assertJsonPath('data.title', 'Mot de passe oublié')
            ->assertJsonPath('data.fields.0.name', 'channel');
        $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/forms/auth.password.reset')->assertOk()
            ->assertJsonPath('data.title', 'New password')
            ->assertJsonPath('data.fields.0.type', 'password')
            ->assertJsonPath('data.fields.1.name', 'password_confirmation');
    }
}
