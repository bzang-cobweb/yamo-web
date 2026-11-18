<?php

namespace Modules\Auth\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserAddress;
use Tests\TestCase;

/**
 * Le rôle `admin` reçoit les permissions des adresses ; le seeder ajoute sans
 * retirer (décision 2026-11-18-contrat-liste-infinie-role-admin).
 */
class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_role_gets_back_office_users_and_address_permissions(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $this->assertEqualsCanonicalizing(
            ['admin.access', 'users.view', 'users.update', 'addresses.view', 'addresses.create', 'addresses.update', 'addresses.delete', 'offers.view', 'offers.create', 'offers.update', 'offers.delete'],
            Role::findByName(RoleEnum::ADMIN->value)->permissions->pluck('name')->all(),
        );
        $this->assertTrue(Role::findByName(RoleEnum::SUPER_ADMIN->value)->permissions->isEmpty());
        $this->assertTrue(Role::findByName(RoleEnum::MEMBER->value)->permissions->isEmpty());
    }

    public function test_replaying_the_seeder_keeps_permissions_given_in_the_back_office(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        Role::findByName(RoleEnum::ADMIN->value)->givePermissionTo(PermissionEnum::VIEW_COUNTRIES);

        $this->seed(AuthDatabaseSeeder::class);

        $permissions = Role::findByName(RoleEnum::ADMIN->value)->fresh()->permissions->pluck('name');
        $this->assertContains(PermissionEnum::VIEW_COUNTRIES->value, $permissions);
        $this->assertContains('addresses.delete', $permissions);
    }

    public function test_an_admin_manages_addresses_in_the_back_office_and_a_plain_account_does_not(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        Filament::setCurrentPanel('admin');
        Filament::setServingStatus();
        $entry = UserAddress::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN);
        $plain = User::factory()->create();

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue($admin->can($ability, $entry), $ability);
            $this->assertFalse($plain->can($ability, $entry), $ability);
        }
        $this->assertTrue($admin->can('create', UserAddress::class));
        $this->assertTrue($admin->can('viewAny', UserAddress::class));
        $this->assertFalse($plain->can('create', UserAddress::class));
    }
}
