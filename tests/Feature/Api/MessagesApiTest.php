<?php

namespace Tests\Feature\Api;

use App\Support\MobileMessages;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * Textes de l'interface de l'app mobile (`GET /api/v1/messages`) et messages
 * d'erreur de l'API traduits.
 */
class MessagesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_are_public_flat_and_in_the_requested_language(): void
    {
        $fr = $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/messages')->assertOk();

        $fr->assertJsonPath('data.locale', 'fr');
        $this->assertSame('Bonjour :name', $fr->json('data.messages')['home.greeting']);
        $this->assertSame('Chargement du formulaire…', $fr->json('data.messages')['form.loading']);
        $this->assertSame('Une erreur est survenue. Réessayez.', $fr->json('data.messages')['error.unknown']);

        $en = $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/messages')->assertOk();
        $en->assertJsonPath('data.locale', 'en');
        $this->assertSame('Hello :name', $en->json('data.messages')['home.greeting']);
    }

    public function test_a_signed_in_user_gets_the_messages_of_their_account_language(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $response = $this->withToken($user->createToken('test')->plainTextToken)
            ->withHeader('Accept-Language', 'fr')
            ->getJson('/api/v1/messages')
            ->assertOk()
            ->assertJsonPath('data.locale', 'en');

        $this->assertSame('Profile', $response->json('data.messages')['profile.title']);
    }

    public function test_every_language_has_exactly_the_same_keys(): void
    {
        $keys = array_map(
            fn (string $locale): array => array_keys(MobileMessages::for($locale)),
            config('app.supported_locales'),
        );

        $this->assertNotEmpty($keys[0]);
        foreach ($keys as $other) {
            $this->assertSame($keys[0], $other);
        }
    }

    public function test_the_app_texts_come_from_every_module_and_from_the_application(): void
    {
        $this->assertContains('mobile', MobileMessages::sources());
        $this->assertContains('auth::mobile', MobileMessages::sources());
        $this->assertContains('form::mobile', MobileMessages::sources());

        $messages = MobileMessages::for('fr');
        foreach (['home.greeting', 'avatar.choose', 'form.retry', 'error.unknown'] as $key) {
            $this->assertArrayHasKey($key, $messages);
        }
    }

    public function test_the_network_message_is_not_served_it_stays_in_the_app(): void
    {
        $this->assertArrayNotHasKey('error.network', MobileMessages::for('fr'));
    }

    public function test_a_missing_or_revoked_token_gets_a_translated_401(): void
    {
        $this->withHeader('Accept-Language', 'fr')->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Vous devez vous connecter.']);

        $this->withHeader('Accept-Language', 'en')->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'You must sign in.']);
    }

    public function test_a_default_refusal_is_translated_but_a_policy_message_is_kept(): void
    {
        // Un paramètre nullable : la règle est évaluée aussi pour un invité.
        Gate::define('test-default', fn (?Authenticatable $user): bool => false);
        Gate::define('test-own-message', fn (?Authenticatable $user): Response => Response::deny('Message propre à la policy.'));
        Route::middleware('api')->get('api/test-default', fn () => Gate::authorize('test-default'));
        Route::middleware('api')->get('api/test-own-message', fn () => Gate::authorize('test-own-message'));

        $this->withHeader('Accept-Language', 'fr')->getJson('/api/test-default')
            ->assertForbidden()
            ->assertExactJson(['message' => "Vous n'avez pas le droit de faire cette action."]);

        $this->withHeader('Accept-Language', 'fr')->getJson('/api/test-own-message')
            ->assertForbidden()
            ->assertJsonPath('message', 'Message propre à la policy.');
    }
}
