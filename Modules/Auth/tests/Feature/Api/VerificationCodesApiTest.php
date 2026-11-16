<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Mail\VerificationCodeMail;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Location\Models\Country;
use Tests\TestCase;

/**
 * Connexion par téléphone, codes (faux, expirés, épuisés, renvoyés), inscription et
 * changements d'e-mail ou de téléphone vérifiés par code.
 */
class VerificationCodesApiTest extends TestCase
{
    use CapturesVerificationCodes;
    use RefreshDatabase;

    private Country $switzerland;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureVerificationCodes();
        $this->switzerland = Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
    }

    private function jeanne(array $attributes = []): User
    {
        return User::factory()->create([
            'email' => 'jeanne@example.com',
            'password' => 'secret-password',
            ...$attributes,
        ]);
    }

    private function verify(string $challenge, string $code, array $extra = []): TestResponse
    {
        return $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $code, ...$extra]);
    }

    private function wrong(string $code): string
    {
        return str_pad((string) (((int) $code + 1) % 1000000), 6, '0', STR_PAD_LEFT);
    }

    // Connexion

    public function test_a_right_password_alone_gives_no_access(): void
    {
        $this->jeanne();

        $this->postJson('/api/v1/auth/login', ['identifier' => 'jeanne@example.com', 'password' => 'secret-password'])
            ->assertAccepted()
            ->assertJsonStructure(['challenge', 'channel', 'destination'])
            ->assertJsonPath('channel', 'email')
            ->assertJsonPath('destination', 'j•••@example.com')
            ->assertJsonMissingPath('token');

        $this->assertSame(0, PersonalAccessToken::count());
        $this->assertSame(1, VerificationCode::count());
        $this->assertNotSame($this->emailCode('jeanne@example.com'), VerificationCode::first()->code_hash);
    }

    public function test_login_with_a_verified_phone_sends_the_code_by_sms(): void
    {
        $user = $this->jeanne(['phone' => '+41791234567', 'phone_country_id' => $this->switzerland->id, 'phone_verified_at' => now()]);

        $challenge = $this->postJson('/api/v1/auth/login', ['identifier' => '+41 79 123 45 67', 'password' => 'secret-password'])
            ->assertAccepted()
            ->assertJsonPath('channel', 'sms')
            ->assertJsonPath('destination', '+41 ••• 67')
            ->json('challenge');

        Mail::assertNothingSent();

        $this->verify($challenge, $this->smsCode('+41791234567'), ['device_name' => 'Pixel'])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonStructure(['token']);
        $this->assertSame(['Pixel'], $user->tokens()->pluck('name')->all());
        $this->assertSame(0, VerificationCode::count());
    }

    public function test_an_unverified_or_national_phone_does_not_sign_in(): void
    {
        $this->jeanne(['phone' => '+41791234567', 'phone_country_id' => $this->switzerland->id]);

        $this->postJson('/api/v1/auth/login', ['identifier' => '+41791234567', 'password' => 'secret-password'])
            ->assertUnprocessable()->assertJsonValidationErrors(['identifier']);
        $this->postJson('/api/v1/auth/login', ['identifier' => '079 123 45 67', 'password' => 'secret-password'])
            ->assertUnprocessable()->assertJsonValidationErrors(['identifier']);
        $this->assertSame([], $this->sms->messages);
    }

    public function test_a_wrong_code_is_refused_and_five_wrong_codes_exhaust_it(): void
    {
        $this->jeanne();
        $challenge = $this->postJson('/api/v1/auth/login', ['identifier' => 'jeanne@example.com', 'password' => 'secret-password'])->json('challenge');
        $code = $this->emailCode('jeanne@example.com');

        for ($i = 1; $i <= 4; $i++) {
            $this->verify($challenge, $this->wrong($code))
                ->assertUnprocessable()
                ->assertJsonPath('errors.code.0', trans('auth::verification.errors.wrong', [], 'en'));
        }

        $this->verify($challenge, $this->wrong($code))->assertJsonPath('errors.code.0', trans('auth::verification.errors.exhausted', [], 'en'));
        $this->verify($challenge, $code)->assertUnprocessable()->assertJsonPath('errors.code.0', trans('auth::verification.errors.exhausted', [], 'en'));
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_an_expired_code_is_refused(): void
    {
        $this->jeanne();
        $challenge = $this->postJson('/api/v1/auth/login', ['identifier' => 'jeanne@example.com', 'password' => 'secret-password'])->json('challenge');
        $code = $this->emailCode('jeanne@example.com');

        $this->travel(11)->minutes();

        $this->verify($challenge, $code)->assertUnprocessable()->assertJsonPath('errors.code.0', trans('auth::verification.errors.expired', [], 'en'));
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_an_unknown_challenge_is_refused(): void
    {
        $this->verify('9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', '123456')
            ->assertUnprocessable()->assertJsonPath('errors.code.0', trans('auth::verification.errors.unknown', [], 'en'));
        $this->postJson('/api/v1/auth/codes/verify', [])->assertUnprocessable()->assertJsonValidationErrors(['challenge', 'code']);
    }

    public function test_a_code_can_be_resent_after_a_minute_and_replaces_the_old_one(): void
    {
        $this->jeanne();
        $challenge = $this->postJson('/api/v1/auth/login', ['identifier' => 'jeanne@example.com', 'password' => 'secret-password'])->json('challenge');
        $first = $this->emailCode('jeanne@example.com');

        $this->postJson('/api/v1/auth/codes/resend', ['challenge' => $challenge])
            ->assertUnprocessable()->assertJsonPath('errors.code.0', trans('auth::verification.errors.too_soon', [], 'en'));

        $this->travel(61)->seconds();
        $this->postJson('/api/v1/auth/codes/resend', ['challenge' => $challenge])->assertNoContent();
        Mail::assertSent(VerificationCodeMail::class, 2);
        $second = $this->emailCode('jeanne@example.com');

        if ($first !== $second) {
            $this->verify($challenge, $first)->assertUnprocessable();
        }
        $this->verify($challenge, $second)->assertOk()->assertJsonStructure(['token']);
    }

    public function test_the_code_email_is_in_the_request_language(): void
    {
        $this->jeanne();
        $this->withHeader('Accept-Language', 'fr')
            ->postJson('/api/v1/auth/login', ['identifier' => 'jeanne@example.com', 'password' => 'secret-password'])
            ->assertAccepted();

        Mail::assertSent(VerificationCodeMail::class, fn (VerificationCodeMail $mail): bool => $mail->locale === 'fr');
    }

    // Inscription

    private function register(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/v1/auth/register', [
            'name' => 'marie',
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'email' => 'marie@example.com',
            'password' => 'Secret-password-1',
            'password_confirmation' => 'Secret-password-1',
            ...$overrides,
        ]);
    }

    public function test_register_with_email_only_creates_the_account_after_the_code(): void
    {
        $challenge = $this->register()->assertAccepted()->assertJsonPath('channel', 'email')->json('challenge');
        $this->assertFalse(User::query()->where('name', 'marie')->exists());

        $response = $this->verify($challenge, $this->emailCode('marie@example.com'), ['device_name' => 'iPhone'])->assertCreated();

        $user = User::query()->where('name', 'marie')->sole();
        $response->assertJsonPath('data.id', $user->id)->assertJsonPath('data.email', 'marie@example.com')->assertJsonStructure(['token']);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->phone);
        $this->assertTrue(Hash::check('Secret-password-1', $user->password));
    }

    public function test_register_with_phone_only_sends_an_sms(): void
    {
        $challenge = $this->register(['email' => null, 'phone' => '079 123 45 67', 'phone_country' => 'CH'])
            ->assertAccepted()->assertJsonPath('channel', 'sms')->json('challenge');
        Mail::assertNothingSent();

        $this->verify($challenge, $this->smsCode('+41791234567'))->assertCreated();

        $user = User::query()->where('name', 'marie')->sole();
        $this->assertSame('+41791234567', $user->phone);
        $this->assertSame($this->switzerland->id, $user->phone_country_id);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNull($user->email);
    }

    public function test_register_with_both_needs_both_codes(): void
    {
        $challenge = $this->register(['phone' => '079 123 45 67', 'phone_country' => 'CH'])->assertAccepted()->json('challenge');

        $this->verify($challenge, $this->emailCode('marie@example.com'), ['channel' => 'email'])
            ->assertAccepted()
            ->assertJsonPath('channel', 'sms')
            ->assertJsonPath('challenge', $challenge)
            ->assertJsonMissingPath('token');
        $this->assertFalse(User::query()->where('name', 'marie')->exists());

        $this->verify($challenge, $this->smsCode('+41791234567'), ['channel' => 'sms'])->assertCreated()->assertJsonStructure(['token']);

        $user = User::query()->where('name', 'marie')->sole();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_register_needs_an_email_or_a_phone_and_refuses_taken_ones(): void
    {
        $this->register(['email' => null])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);
        $this->register(['email' => null, 'phone' => '123', 'phone_country' => 'CH'])->assertUnprocessable()->assertJsonValidationErrors(['phone']);

        $this->jeanne(['name' => 'marie', 'phone' => '+41791234567']);
        $this->register(['email' => 'jeanne@example.com', 'phone' => '079 123 45 67', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'phone']);

        Mail::assertNothingSent();
        $this->assertSame(0, VerificationCode::count());
    }

    // Changer d'e-mail ou de téléphone

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_changing_email_needs_the_code_sent_to_the_new_address(): void
    {
        $user = $this->jeanne(['email_verified_at' => now()->subYear()]);
        $token = $this->token($user);

        $challenge = $this->withToken($token)->postJson('/api/v1/auth/me/email', ['email' => 'jeanne@new.example.com'])
            ->assertAccepted()->assertJsonPath('destination', 'j•••@new.example.com')->json('challenge');

        $this->assertSame('jeanne@example.com', $user->fresh()->email);
        Mail::assertNotSent(VerificationCodeMail::class, fn (VerificationCodeMail $mail): bool => $mail->hasTo('jeanne@example.com'));

        $this->withToken($token)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode('jeanne@new.example.com')])
            ->assertOk()
            ->assertJsonPath('data.email', 'jeanne@new.example.com')
            ->assertJsonMissingPath('token');
        $this->assertSame('jeanne@new.example.com', $user->fresh()->email);
    }

    public function test_a_change_code_is_only_valid_for_its_own_account(): void
    {
        $user = $this->jeanne();
        $other = User::factory()->create();
        $challenge = $this->withToken($this->token($user))->postJson('/api/v1/auth/me/email', ['email' => 'jeanne@new.example.com'])->json('challenge');
        $code = $this->emailCode('jeanne@new.example.com');

        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->verify($challenge, $code)->assertUnprocessable()->assertJsonPath('errors.code.0', trans('auth::verification.errors.unknown', [], 'en'));

        $this->app['auth']->forgetGuards();
        $this->withToken($this->token($other))->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $code])->assertUnprocessable();

        $this->assertSame('jeanne@example.com', $user->fresh()->email);
    }

    public function test_a_value_taken_while_the_code_was_pending_is_refused_cleanly(): void
    {
        $user = $this->jeanne();
        $token = $this->token($user);
        $challenge = $this->withToken($token)->postJson('/api/v1/auth/me/email', ['email' => 'jeanne@new.example.com'])->json('challenge');
        User::factory()->create(['email' => 'jeanne@new.example.com']);

        $this->withToken($token)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode('jeanne@new.example.com')])
            ->assertUnprocessable()->assertJsonValidationErrors(['code']);
        $this->assertSame('jeanne@example.com', $user->fresh()->email);

        $this->flushHeaders();
        $register = $this->register(['email' => 'late@example.com'])->json('challenge');
        User::factory()->create(['email' => 'late@example.com']);
        $this->verify($register, $this->emailCode('late@example.com'))->assertUnprocessable()->assertJsonValidationErrors(['code']);
        $this->assertFalse(User::query()->where('name', 'marie')->exists());
    }

    public function test_an_email_taken_by_someone_else_is_refused(): void
    {
        $user = $this->jeanne();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->withToken($this->token($user))->postJson('/api/v1/auth/me/email', ['email' => 'taken@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/me/email', ['email' => 'x@example.com'])->assertUnauthorized();
    }

    public function test_adding_a_phone_needs_the_sms_code(): void
    {
        $user = $this->jeanne();
        $token = $this->token($user);

        $challenge = $this->withToken($token)->postJson('/api/v1/auth/me/phone', ['phone' => '079 123 45 67', 'phone_country' => 'CH'])
            ->assertAccepted()->assertJsonPath('channel', 'sms')->json('challenge');
        $this->assertNull($user->fresh()->phone);

        $this->withToken($token)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->smsCode('+41791234567')])
            ->assertOk()
            ->assertJsonPath('data.phone', '+41791234567')
            ->assertJsonPath('data.phone_country', 'CH');

        $user->refresh();
        $this->assertSame('+41791234567', $user->phone);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_an_invalid_or_taken_phone_is_refused(): void
    {
        $user = $this->jeanne();
        User::factory()->create(['phone' => '+41791234567']);
        $token = $this->token($user);

        $this->withToken($token)->postJson('/api/v1/auth/me/phone', ['phone' => '123', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonValidationErrors(['phone']);
        $this->withToken($token)->postJson('/api/v1/auth/me/phone', ['phone' => '079 123 45 67', 'phone_country' => 'CH'])
            ->assertUnprocessable()->assertJsonValidationErrors(['phone']);
        $this->assertSame([], $this->sms->messages);
    }

    // Formulaires

    public function test_register_and_code_forms_are_public(): void
    {
        $this->getJson('/api/v1/forms/auth.register')->assertOk()
            ->assertJsonPath('data.fields.4.name', 'phone')
            ->assertJsonPath('data.fields.4.type', 'phone');
        $this->getJson('/api/v1/forms/auth.code')->assertOk()
            ->assertJsonPath('data.fields.0.type', 'code');
    }

    public function test_change_forms_show_the_current_value_read_only(): void
    {
        $user = $this->jeanne(['phone' => '+41791234567', 'phone_country_id' => $this->switzerland->id, 'locale' => 'fr']);
        $token = $this->token($user);

        $this->withToken($token)->getJson('/api/v1/forms/auth.profile.email')->assertOk()
            ->assertJsonPath('data.title', "Changer d'adresse e-mail")
            ->assertJsonPath('data.fields.0.name', 'current_email')
            ->assertJsonPath('data.fields.0.readonly', true)
            ->assertJsonPath('data.fields.0.default', 'jeanne@example.com')
            ->assertJsonPath('data.fields.1.name', 'email');

        $this->withToken($token)->getJson('/api/v1/forms/auth.profile.phone')->assertOk()
            ->assertJsonPath('data.fields.0.name', 'current_phone')
            ->assertJsonPath('data.fields.0.type', 'phone')
            ->assertJsonPath('data.fields.0.readonly', true)
            ->assertJsonPath('data.fields.0.default.country', 'CH');

        $newcomer = User::factory()->create(['email' => null, 'phone' => '+41791234500', 'locale' => 'fr']);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token($newcomer))->getJson('/api/v1/forms/auth.profile.email')->assertOk()
            ->assertJsonPath('data.title', 'Ajouter une adresse e-mail')
            ->assertJsonPath('data.fields.0.name', 'email');
    }
}
