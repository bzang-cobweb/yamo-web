<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Location\Models\Country;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Suppression de son compte depuis l'app (`DELETE /api/v1/auth/me`) :
 * confirmée par le mot de passe, récupérable pendant le délai réglé, puis purgée
 * (décision 2026-11-20-suppression-de-son-compte).
 */
class AccountDeletionApiTest extends TestCase
{
    use CapturesVerificationCodes;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthDatabaseSeeder::class);
        $this->captureVerificationCodes();
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(['email' => 'jeanne@example.com', 'password' => 'secret-password', 'locale' => 'fr', ...$attributes]);
    }

    public function test_the_form_warns_with_the_configured_delay_and_asks_for_the_password(): void
    {
        config(['auth.account_deletion.grace_days' => 14]);
        $user = $this->user();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->withHeader('Accept-Language', 'fr')
            ->getJson('/api/v1/forms/auth.profile.delete-account')
            ->assertOk()
            ->assertJsonPath('data.title', 'Supprimer mon compte')
            ->assertJsonPath('data.fields.0.name', 'current_password')
            ->assertJsonPath('data.fields.0.type', 'password')
            ->assertJson(fn ($json) => $json->where('data.subtitle', fn (string $subtitle) => str_contains($subtitle, '14 jours'))->etc());
    }

    public function test_deleting_with_the_password_deactivates_the_account_and_signs_out_every_device(): void
    {
        $user = $this->user();
        $token = $user->createToken('phone')->plainTextToken;
        $user->createToken('tablet');

        $this->withToken($token)->deleteJson('/api/v1/auth/me', ['current_password' => 'secret-password'])->assertNoContent();

        $this->assertSoftDeleted($user);
        $this->assertSame(0, $user->tokens()->count());

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
        // Se reconnecter ne rouvre pas la session : l'app propose de récupérer le compte.
        $challenge = $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => 'jeanne@example.com', 'password' => 'secret-password'])
            ->assertAccepted()->json('challenge');
        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode('jeanne@example.com')])
            ->assertOk()->assertJsonPath('next', 'restore')->assertJsonMissingPath('token');
        $this->assertSoftDeleted($user);
    }

    public function test_the_owner_recovers_the_account_after_signing_in(): void
    {
        config(['auth.account_deletion.grace_days' => 30]);
        $user = $this->user();
        $this->travelTo(now()->subDays(10), fn () => $user->delete());

        $challenge = $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => 'jeanne@example.com', 'password' => 'secret-password'])->json('challenge');
        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode('jeanne@example.com')])
            ->assertOk()
            ->assertJsonPath('next', 'restore')
            ->assertJsonPath('challenge', $challenge)
            ->assertJsonPath('deletion_date', now()->addDays(20)->toDateString());

        $token = $this->postJson('/api/v1/auth/account/restore', ['challenge' => $challenge, 'device_name' => 'iPhone'])
            ->assertOk()
            ->assertJsonPath('data.email', 'jeanne@example.com')
            ->json('token');

        $this->assertNotSoftDeleted($user);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();

        // Le défi est fermé : il ne resservira pas.
        $this->postJson('/api/v1/auth/account/restore', ['challenge' => $challenge])->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_restore_needs_a_validated_login_code_on_a_deleted_account(): void
    {
        $user = $this->user();
        $user->delete();

        // Code non validé.
        $challenge = $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => 'jeanne@example.com', 'password' => 'secret-password'])->json('challenge');
        $this->postJson('/api/v1/auth/account/restore', ['challenge' => $challenge])->assertUnprocessable()->assertJsonValidationErrors('code');

        // Défi inconnu.
        $this->postJson('/api/v1/auth/account/restore', ['challenge' => (string) Str::uuid()])->assertUnprocessable();

        // Code validé trop tard.
        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode('jeanne@example.com')])->assertOk();
        $this->travel(config('auth.verification_codes.minutes') + 1)->minutes();
        $this->postJson('/api/v1/auth/account/restore', ['challenge' => $challenge])->assertUnprocessable();
        $this->assertSoftDeleted($user);
    }

    public function test_a_purged_account_cannot_be_recovered(): void
    {
        $user = $this->user();
        $user->forceDelete();

        $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => 'jeanne@example.com', 'password' => 'secret-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_a_wrong_password_is_refused_and_the_account_stays(): void
    {
        $user = $this->user();

        $this->withToken($user->createToken('test')->plainTextToken)
            ->deleteJson('/api/v1/auth/me', ['current_password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertNotSoftDeleted($user);
    }

    public function test_a_guest_cannot_delete_an_account(): void
    {
        $this->deleteJson('/api/v1/auth/me', ['current_password' => 'x'])->assertUnauthorized();
    }

    public function test_the_last_super_admin_cannot_delete_their_account(): void
    {
        $admin = $this->user();
        $admin->assignRole(RoleEnum::SUPER_ADMIN);

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->withHeader('Accept-Language', 'fr')
            ->deleteJson('/api/v1/auth/me', ['current_password' => 'secret-password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.current_password.0', 'Vous êtes le dernier super administrateur : nommez-en un autre avant de supprimer votre compte.');

        $this->assertNotSoftDeleted($admin);
    }

    public function test_a_super_admin_can_delete_their_account_when_another_remains(): void
    {
        $admin = $this->user();
        $admin->assignRole(RoleEnum::SUPER_ADMIN);
        User::factory()->create()->assignRole(RoleEnum::SUPER_ADMIN);

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->deleteJson('/api/v1/auth/me', ['current_password' => 'secret-password'])
            ->assertNoContent();

        $this->assertSoftDeleted($admin);
    }

    public function test_the_email_stays_reserved_until_the_purge(): void
    {
        $user = $this->user(['name' => 'jeanne']);
        $user->delete();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'autre', 'first_name' => 'Autre', 'last_name' => 'Personne', 'gender' => 'female',
            'email' => 'jeanne@example.com', 'password' => 'Secret-password-1', 'password_confirmation' => 'Secret-password-1',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_the_username_phone_and_email_stay_reserved_until_the_purge(): void
    {
        $country = Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
        User::factory()->create(['name' => 'jeanne', 'email' => 'held@example.com', 'phone' => '+41791234567', 'phone_country_id' => $country->getKey()])->delete();
        $other = $this->user();
        $token = $other->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/auth/register', [
            'name' => 'jeanne', 'first_name' => 'A', 'last_name' => 'B', 'gender' => 'female',
            'phone' => '079 123 45 67', 'phone_country' => 'CH',
            'password' => 'Secret-password-1', 'password_confirmation' => 'Secret-password-1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'phone']);

        $this->withToken($token)->postJson('/api/v1/auth/me/email', ['email' => 'held@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_the_purge_removes_only_accounts_past_the_configured_delay(): void
    {
        config(['auth.account_deletion.grace_days' => 30]);
        $old = User::factory()->create();
        $recent = User::factory()->create();
        $active = User::factory()->create();

        $this->travelTo(now()->subDays(31), fn () => $old->delete());
        $this->travelTo(now()->subDays(29), fn () => $recent->delete());

        $this->artisan('auth:purge-deleted-accounts')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertSoftDeleted($recent);
        $this->assertNotSoftDeleted($active);
    }

    public function test_the_purge_deletes_the_photo_too(): void
    {
        Storage::fake('public');
        $user = $this->user();
        $this->withToken($user->createToken('test')->plainTextToken)
            ->post('/api/v1/auth/me/avatar', ['avatar' => UploadedFile::fake()->image('a.jpg', 800, 800)], ['Accept' => 'application/json'])
            ->assertOk();
        $this->travelTo(now()->subDays(31), fn () => $user->fresh()->delete());
        $this->assertSame(1, Media::count());

        $this->artisan('auth:purge-deleted-accounts')->assertSuccessful();

        $this->assertSame(0, Media::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_purge_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('auth:purge-deleted-accounts')->assertSuccessful();
    }
}
