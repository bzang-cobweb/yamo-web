<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * Comptes déjà inscrits sans rôle : `member` par la migration 2026_11_22_120000,
 * retiré à eux seuls au retour en arrière (décision 2026-11-18-role-membre).
 */
class MemberRoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require module_path('Auth', 'database/migrations/2026_11_22_120000_give_member_role_to_accounts_without_role.php');
    }

    public function test_accounts_without_role_become_members_and_only_they_lose_it_on_rollback(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $migration = $this->migration();
        $migration->down();

        $plain = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::ADMIN);
        $member = User::factory()->create();

        $migration->up();
        // Un membre inscrit après la migration, par l'app.
        $later = User::factory()->create();
        $later->assignRole(RoleEnum::MEMBER);

        $this->assertSame(['member'], $plain->fresh()->getRoleNames()->all());
        $this->assertSame(['member'], $member->fresh()->getRoleNames()->all());
        $this->assertSame(['admin'], $admin->fresh()->getRoleNames()->all());

        $migration->down();

        $this->assertSame([], $plain->fresh()->getRoleNames()->all());
        $this->assertSame(['admin'], $admin->fresh()->getRoleNames()->all());
        $this->assertSame(['member'], $later->fresh()->getRoleNames()->all());
    }
}
