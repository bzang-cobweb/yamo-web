<?php

namespace Modules\Auth\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Tests\TestCase;

abstract class AuthFilamentTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthDatabaseSeeder::class);

        Filament::setCurrentPanel('admin');
        Filament::setServingStatus();
    }

    /**
     * Connecte un compte qui a exactement les permissions données.
     */
    protected function actingAsUserWith(PermissionEnum ...$permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([PermissionEnum::ACCESS_ADMIN, ...$permissions]);
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }
}
