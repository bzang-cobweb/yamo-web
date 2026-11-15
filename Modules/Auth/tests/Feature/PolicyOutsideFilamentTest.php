<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Permission;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * Branche « site et API » des policies : Filament n'est pas en service.
 */
class PolicyOutsideFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthDatabaseSeeder::class);
    }

    public function test_a_user_sees_and_updates_only_its_own_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->assertTrue($user->can('view', $user));
        $this->assertTrue($user->can('update', $user));
        $this->assertFalse($user->can('view', $other));
        $this->assertFalse($user->can('update', $other));
        $this->assertFalse($user->can('delete', $user));
    }

    public function test_back_office_permissions_grant_nothing_outside_filament(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionEnum::cases());
        $other = User::factory()->create();
        $role = Role::findByName(RoleEnum::SUPER_ADMIN->value);
        $permission = Permission::findByName(PermissionEnum::VIEW_USERS->value);

        $this->assertFalse($user->can('viewAny', User::class));
        $this->assertFalse($user->can('create', User::class));
        $this->assertFalse($user->can('update', $other));
        $this->assertFalse($user->can('delete', $other));
        $this->assertFalse($user->can('viewAny', Role::class));
        $this->assertFalse($user->can('update', $role));
        $this->assertFalse($user->can('viewAny', Permission::class));
        $this->assertFalse($user->can('view', $permission));
    }

    public function test_super_admin_has_every_ability(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SUPER_ADMIN);

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('viewAny', Role::class));
    }

    public function test_panel_access_requires_the_access_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();

        $user->givePermissionTo(PermissionEnum::ACCESS_ADMIN);
        $this->actingAs($user->fresh())->get('/admin')->assertOk();
    }

    public function test_guest_is_redirected_to_the_login_page(): void
    {
        $this->get('/admin/users')->assertRedirect('/admin/login');
    }
}
