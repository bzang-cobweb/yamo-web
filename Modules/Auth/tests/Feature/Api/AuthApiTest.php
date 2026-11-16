<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use CapturesVerificationCodes;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captureVerificationCodes();
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create([
            'email' => 'jeanne@example.com',
            'password' => 'secret-password',
            ...$attributes,
        ]);
    }

    /**
     * Première étape : identifiants.
     */
    private function login(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/v1/auth/login', [
            'identifier' => 'jeanne@example.com',
            'password' => 'secret-password',
            ...$overrides,
        ]);
    }

    /**
     * Connexion complète : identifiants, puis le code reçu par e-mail.
     */
    private function signIn(string $deviceName = 'iPhone de Jeanne'): TestResponse
    {
        $challenge = $this->login()->assertAccepted()->json('challenge');

        return $this->postJson('/api/v1/auth/codes/verify', [
            'challenge' => $challenge,
            'code' => $this->emailCode('jeanne@example.com'),
            'device_name' => $deviceName,
        ]);
    }

    public function test_login_returns_a_token_and_the_user_without_sensitive_data(): void
    {
        $user = $this->user([
            'name' => 'jeanne', 'first_name' => 'Jeanne', 'last_name' => 'Dupont', 'locale' => 'en',
            'gender' => 'female', 'birth_date' => '1990-04-12',
        ]);

        $response = $this->signIn()->assertOk();

        $response->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => 'jeanne',
                'first_name' => 'Jeanne',
                'last_name' => 'Dupont',
                'full_name' => 'Jeanne Dupont',
                'initials' => 'JD',
                'gender' => 'female',
                'birth_date' => '1990-04-12',
                'country' => null,
                'region' => null,
                'email' => 'jeanne@example.com',
                'phone' => null,
                'phone_country' => null,
                'last_seen_at' => null,
                'is_online' => false,
                'locale' => 'en',
                'avatar_url' => null,
            ],
            'token' => $response->json('token'),
        ]);
        $response->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.roles')
            ->assertJsonMissingPath('data.remember_token');

        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('iPhone de Jeanne', $user->tokens()->first()->name);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_with_wrong_password_returns_422_and_creates_no_token(): void
    {
        $this->user();

        $this->login(['password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['identifier'])
            ->assertJsonMissingPath('token');

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_login_with_unknown_email_gives_the_same_answer(): void
    {
        $this->user();

        $wrongPassword = $this->login(['password' => 'wrong'])->json('errors');
        $unknownEmail = $this->login(['identifier' => 'nobody@example.com'])->assertUnprocessable()->json('errors');

        $this->assertSame($wrongPassword, $unknownEmail);
    }

    public function test_login_validates_its_fields(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['identifier', 'password']);
    }

    public function test_login_error_is_translated_from_accept_language(): void
    {
        $this->user();

        // Le client de test de Symfony envoie « en-us » par défaut : chaque langue est demandée.
        $fr = $this->withHeader('Accept-Language', 'fr-CH,fr;q=0.9')->login(['password' => 'wrong'])->json('errors.identifier.0');
        $en = $this->withHeader('Accept-Language', 'en')->login(['password' => 'wrong'])->json('errors.identifier.0');

        $this->assertSame(trans('auth.failed', [], 'fr'), $fr);
        $this->assertSame(trans('auth.failed', [], 'en'), $en);
        $this->assertNotSame($fr, $en);
    }

    public function test_sixth_attempt_within_a_minute_is_throttled(): void
    {
        $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->login(['password' => 'wrong'])->assertUnprocessable();
        }

        $this->login(['password' => 'wrong'])->assertTooManyRequests();
        $this->login()->assertTooManyRequests();
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = $this->user(['name' => 'jeanne']);
        $token = $this->signIn()->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'jeanne')
            ->assertJsonMissingPath('data.password');
    }

    public function test_me_without_token_returns_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_answers_in_the_user_locale(): void
    {
        $this->user(['locale' => 'en']);
        $token = $this->signIn()->json('token');

        $this->withToken($token)->withHeader('Accept-Language', 'fr')->getJson('/api/v1/auth/me')->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = $this->user();
        $phone = $this->signIn()->json('token');
        $this->travel(61)->seconds();
        $tablet = $this->signIn('iPad')->json('token');

        $this->withToken($phone)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertSame(['iPad'], $user->tokens()->pluck('name')->all());

        // Le garde mémorise l'utilisateur entre deux appels d'un même test.
        $this->app['auth']->forgetGuards();
        $this->withToken($phone)->getJson('/api/v1/auth/me')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($tablet)->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_logout_without_token_returns_401(): void
    {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    public function test_the_former_default_user_route_is_gone(): void
    {
        $user = $this->user();
        $token = $this->signIn()->json('token');

        $this->withToken($token)->getJson('/api/user')->assertNotFound();
        $this->assertNotNull($user);
    }
}
