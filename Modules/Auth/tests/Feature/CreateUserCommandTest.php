<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
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
            '--first-name' => 'Jeanne',
            '--last-name' => 'Dupont',
            '--email' => 'jeanne@example.com',
            '--password' => 'secret-password',
            '--locale' => 'en',
            '--super-admin' => true,
        ])->assertSuccessful();

        $user = User::firstWhere('email', 'jeanne@example.com');
        $this->assertSame('Jeanne Dupont', $user->full_name);
        $this->assertSame('en', $user->locale);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertTrue($user->hasRole(RoleEnum::SUPER_ADMIN));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_creates_a_plain_user_without_role(): void
    {
        $this->artisan('auth:create-user', [
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
            '--first-name' => 'Jean',
            '--last-name' => 'Martin',
            '--email' => 'taken@example.com',
            '--password' => 'secret-password',
            '--locale' => 'de',
        ])->assertFailed();

        $this->assertSame(1, User::count());
    }
}
