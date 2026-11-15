<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Reprise des pseudonymes par la migration 2026_11_15_170000.
 */
class RestorePseudonymMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_users_receive_a_unique_pseudonym_from_their_full_name(): void
    {
        $migration = require module_path('Auth', 'database/migrations/2026_11_15_170000_restore_user_name_as_unique_pseudonym.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('users', 'name'));

        foreach ([['Jeanne', 'Dupont'], ['Jeanne', 'Dupont'], ['Jean', ''], ['', '']] as $i => [$first, $last]) {
            DB::table('users')->insert([
                'first_name' => $first,
                'last_name' => $last,
                'email' => "user{$i}@example.com",
                'password' => 'x',
            ]);
        }

        $migration->up();

        $this->assertSame(
            ['Jeanne Dupont', 'Jeanne Dupont 2', 'Jean', 'user'],
            DB::table('users')->orderBy('email')->pluck('name')->all(),
        );
    }
}
