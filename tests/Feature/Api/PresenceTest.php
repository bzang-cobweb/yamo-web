<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * Statut « en ligne » : `last_seen_at` écrit à chaque appel de l'app (au plus une
 * fois par minute), `is_online` vrai pendant 5 minutes.
 */
class PresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_call_marks_the_account_online_without_touching_updated_at(): void
    {
        $user = User::factory()->create(['last_seen_at' => null]);
        $updatedAt = $user->updated_at;
        $this->travel(1)->hours();

        $this->withToken($user->createToken('test')->plainTextToken)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.is_online', true);

        $user->refresh();
        $this->assertNotNull($user->last_seen_at);
        $this->assertTrue($user->updated_at->equalTo($updatedAt));
    }

    public function test_an_account_is_offline_after_five_minutes_without_calls(): void
    {
        $user = User::factory()->create(['last_seen_at' => now()]);
        $this->assertTrue($user->is_online);

        $this->travel(User::ONLINE_MINUTES + 1)->minutes();

        $this->assertFalse($user->fresh()->is_online);
    }

    public function test_last_seen_is_written_at_most_once_a_minute(): void
    {
        $seen = now()->subSeconds(30)->startOfSecond();
        $user = User::factory()->create(['last_seen_at' => $seen]);

        $this->withToken($user->createToken('test')->plainTextToken)->getJson('/api/v1/auth/me')->assertOk();

        $this->assertTrue($user->fresh()->last_seen_at->equalTo($seen));
    }
}
