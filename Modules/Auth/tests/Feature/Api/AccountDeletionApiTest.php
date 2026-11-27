<?php

namespace Modules\Auth\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Support\Masks;
use Modules\Auth\Tests\Concerns\CapturesVerificationCodes;
use Modules\Location\Models\Country;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Suppression de son compte depuis l'app (`DELETE /api/v1/auth/me`) :
 * une raison, puis un code (décision 2026-11-27-confidentialite), récupérable pendant le délai réglé, puis purgée
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

    public function test_the_form_warns_with_the_configured_delay_and_asks_for_a_reason(): void
    {
        config(['auth.account_deletion.grace_days' => 14]);
        $user = $this->user();

        // Une raison obligatoire, plus de mot de passe (décision 2026-11-27-confidentialite).
        $this->withToken($user->createToken('test')->plainTextToken)
            ->withHeader('Accept-Language', 'fr')
            ->getJson('/api/v1/forms/auth.profile.delete-account')
            ->assertOk()
            ->assertJsonPath('data.title', 'Supprimer mon compte')
            ->assertJsonCount(1, 'data.fields')
            ->assertJsonPath('data.fields.0.name', 'reason')
            ->assertJsonPath('data.fields.0.type', 'textarea')
            ->assertJsonPath('data.fields.0.required', true)
            ->assertJsonPath('data.submit.success.action', 'sign-out')
            ->assertJson(fn ($json) => $json->where('data.subtitle', fn (string $subtitle) => str_contains($subtitle, '14 jours'))->etc());
    }

    public function test_deleting_with_a_reason_and_the_code_deactivates_the_account_and_signs_out_every_device(): void
    {
        $user = $this->user(['email_verified_at' => now()]);
        $token = $user->createToken('phone')->plainTextToken;
        $user->createToken('tablet');

        // La raison, puis un code à l'e-mail vérifié : rien n'est supprimé avant sa validation.
        $challenge = $this->withToken($token)->deleteJson('/api/v1/auth/me', ['reason' => 'Je pars en voyage.'])
            ->assertAccepted()
            ->assertJsonPath('channel', 'email')
            ->assertJsonPath('destination', Masks::email($user->email))
            ->json('challenge');
        $this->assertNotSoftDeleted($user);

        $this->withToken($token)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode($user->email)])
            ->assertOk()
            ->assertJsonPath('data.id', $user->getKey())
            ->assertJsonMissingPath('token');

        $this->assertSoftDeleted($user);
        $this->assertSame(0, $user->tokens()->count());
        // La raison, gardée pour l'équipe.
        $this->assertDatabaseHas('account_deletion_reasons', ['user_id' => $user->getKey(), 'reason' => 'Je pars en voyage.']);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
        // Se reconnecter ne rouvre pas la session : l'app propose de récupérer le compte.
        $challenge = $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => $user->email])
            ->assertAccepted()->json('challenge');
        $this->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode($user->email)])
            ->assertOk()->assertJsonPath('next', 'restore')->assertJsonMissingPath('token');
        $this->assertSoftDeleted($user);
    }

    public function test_the_code_goes_to_the_verified_phone_when_the_email_is_not_verified(): void
    {
        $country = Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
        $user = $this->user(['phone' => '+41791234567', 'phone_country_id' => $country->getKey(), 'phone_verified_at' => now(), 'email_verified_at' => null]);

        $this->withToken($user->createToken('test')->plainTextToken)->deleteJson('/api/v1/auth/me', ['reason' => 'Plus besoin.'])
            ->assertAccepted()
            ->assertJsonPath('channel', 'sms')
            ->assertJsonPath('destination', Masks::phone('+41791234567'));
    }

    public function test_the_reason_is_required_and_the_code_must_be_right(): void
    {
        $user = $this->user(['email_verified_at' => now()]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/v1/auth/me', ['reason' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors('reason');

        $challenge = $this->withToken($token)->deleteJson('/api/v1/auth/me', ['reason' => 'Trop de messages.'])->assertAccepted()->json('challenge');
        $this->withToken($token)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode($user->email) === '000000' ? '111111' : '000000'])
            ->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->assertNotSoftDeleted($user);
        $this->assertDatabaseCount('account_deletion_reasons', 0);
    }

    public function test_another_account_cannot_validate_the_deletion_code(): void
    {
        $user = $this->user(['email_verified_at' => now()]);
        $other = User::factory()->create();

        $challenge = $this->withToken($user->createToken('test')->plainTextToken)->deleteJson('/api/v1/auth/me', ['reason' => 'Au revoir.'])->json('challenge');
        $this->app['auth']->forgetGuards();
        $this->withToken($other->createToken('test')->plainTextToken)
            ->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode($user->email)])
            ->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->assertNotSoftDeleted($user);
    }

    public function test_the_owner_recovers_the_account_after_signing_in(): void
    {
        config(['auth.account_deletion.grace_days' => 30]);
        $user = $this->user();
        $this->travelTo(now()->subDays(10), fn () => $user->delete());

        $challenge = $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => 'jeanne@example.com'])->json('challenge');
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
        $challenge = $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => 'jeanne@example.com'])->json('challenge');
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

        $this->postJson('/api/v1/auth/login', ['channel' => 'email', 'email' => $user->email])
            // Plus aucune trace : l'identifiant mène à l'inscription.
            ->assertOk()->assertJsonPath('next', 'register');
    }

    public function test_a_guest_cannot_delete_an_account(): void
    {
        $this->deleteJson('/api/v1/auth/me', ['reason' => 'x'])->assertUnauthorized();
    }

    public function test_the_last_super_admin_cannot_delete_their_account(): void
    {
        $admin = $this->user(['email_verified_at' => now()]);
        $admin->assignRole(RoleEnum::SUPER_ADMIN);

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->withHeader('Accept-Language', 'fr')
            ->deleteJson('/api/v1/auth/me', ['reason' => 'Je pars.'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.reason.0', 'Vous êtes le dernier super administrateur : nommez-en un autre avant de supprimer votre compte.');

        $this->assertNotSoftDeleted($admin);
    }

    public function test_a_super_admin_can_delete_their_account_when_another_remains(): void
    {
        $admin = $this->user(['email_verified_at' => now()]);
        $admin->assignRole(RoleEnum::SUPER_ADMIN);
        User::factory()->create()->assignRole(RoleEnum::SUPER_ADMIN);
        $token = $admin->createToken('test')->plainTextToken;

        $challenge = $this->withToken($token)->deleteJson('/api/v1/auth/me', ['reason' => 'Je pars.'])->assertAccepted()->json('challenge');
        $this->withToken($token)->postJson('/api/v1/auth/codes/verify', ['challenge' => $challenge, 'code' => $this->emailCode($admin->email)])->assertOk();

        $this->assertSoftDeleted($admin);
    }

    public function test_the_email_stays_reserved_until_the_purge(): void
    {
        $user = $this->user(['name' => 'jeanne']);
        $user->delete();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'autre', 'first_name' => 'Autre', 'last_name' => 'Personne',
            'email' => 'jeanne@example.com',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_the_username_phone_and_email_stay_reserved_until_the_purge(): void
    {
        $country = Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);
        User::factory()->create(['name' => 'jeanne', 'email' => 'held@example.com', 'phone' => '+41791234567', 'phone_country_id' => $country->getKey()])->delete();
        $other = $this->user();
        $token = $other->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/auth/register', [
            'name' => 'jeanne', 'first_name' => 'A', 'last_name' => 'B',
            'phone' => '079 123 45 67', 'phone_country' => 'CH',
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
