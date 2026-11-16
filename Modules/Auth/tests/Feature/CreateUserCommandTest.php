<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthDatabaseSeeder::class);
    }

    public function test_creates_a_super_admin(): void
    {
        $this->artisan('auth:create-user', [
            '--name' => 'jeanne',
            '--first-name' => 'Jeanne',
            '--last-name' => 'Dupont',
            '--email' => 'jeanne@example.com',
            '--password' => 'secret-password',
            '--locale' => 'en',
            '--super-admin' => true,
        ])->assertSuccessful();

        $user = User::firstWhere('email', 'jeanne@example.com');
        $this->assertSame('jeanne', $user->name);
        $this->assertSame('Jeanne Dupont', $user->full_name);
        $this->assertSame('en', $user->locale);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertTrue($user->hasRole(RoleEnum::SUPER_ADMIN));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_creates_a_plain_user_without_role(): void
    {
        $this->artisan('auth:create-user', [
            '--name' => 'jean',
            '--first-name' => 'Jean',
            '--last-name' => 'Martin',
            '--email' => 'jean@example.com',
            '--password' => 'secret-password',
            '--locale' => 'fr',
        ])->assertSuccessful();

        $this->assertCount(0, User::firstWhere('email', 'jean@example.com')->roles);
    }

    public function test_refuses_an_unsupported_locale_and_a_taken_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->artisan('auth:create-user', [
            '--name' => 'jean',
            '--first-name' => 'Jean',
            '--last-name' => 'Martin',
            '--email' => 'taken@example.com',
            '--password' => 'secret-password',
            '--locale' => 'de',
        ])->assertFailed();

        $this->assertSame(1, User::count());
    }

    public function test_refuses_a_taken_pseudonym(): void
    {
        User::factory()->create(['name' => 'jeanne']);

        $this->artisan('auth:create-user', [
            '--name' => 'jeanne',
            '--first-name' => 'Jeanne',
            '--last-name' => 'Dupont',
            '--email' => 'jeanne@example.com',
            '--password' => 'secret-password',
            '--locale' => 'fr',
        ])->assertFailed();

        $this->assertSame(1, User::count());
    }

    public function test_creates_a_user_with_a_phone_only_stored_in_e164(): void
    {
        Country::factory()->create(['code' => 'CH', 'dial_code' => '+41']);

        $this->artisan('auth:create-user', [
            '--name' => 'paul', '--first-name' => 'Paul', '--last-name' => 'Martin',
            '--phone' => '079 123 45 67', '--phone-country' => 'ch',
            '--password' => 'secret-password', '--locale' => 'fr',
        ])->assertSuccessful();

        $user = User::firstWhere('name', 'paul');
        $this->assertNull($user->email);
        $this->assertSame('+41791234567', $user->phone);
        $this->assertSame('CH', $user->phoneCountry->code);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_refuses_an_account_without_email_nor_phone_and_an_invalid_phone(): void
    {
        Country::factory()->create(['code' => 'CH']);

        $this->artisan('auth:create-user', [
            '--name' => 'paul', '--first-name' => 'Paul', '--last-name' => 'Martin',
            '--phone' => '123', '--phone-country' => 'CH', '--password' => 'x', '--locale' => 'fr',
        ])->assertFailed();

        $this->assertSame(0, User::where('name', 'paul')->count());
    }
}
