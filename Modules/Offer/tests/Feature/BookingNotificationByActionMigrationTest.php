<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Offer\Notifications\BookingRequestCancelled;
use Modules\Offer\Notifications\BookingRequestReceived;
use Modules\Offer\Notifications\BookingRequestUpdated;
use Tests\TestCase;

/**
 * Les notifications `BookingRequested` prennent la classe de leur étape (décision 2026-11-22-notification-par-action).
 */
class BookingNotificationByActionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_merged_booking_notifications_take_the_class_of_their_step(): void
    {
        $user = User::factory()->create();
        $insert = fn (string $type, array $data) => DB::table('notifications')->insert([
            'id' => $id = (string) Str::uuid(), 'type' => $type, 'notifiable_type' => $user->getMorphClass(), 'notifiable_id' => $user->getKey(),
            'data' => json_encode($data), 'created_at' => now(), 'updated_at' => now(),
        ]) ? $id : '';
        $merged = 'Modules\\Offer\\Notifications\\BookingRequested';
        $cancelled = $insert($merged, ['step' => 'cancelled', 'title' => 'Annulée']);
        $updated = $insert($merged, ['step' => 'updated']);
        $noStep = $insert($merged, ['title' => 'Ancienne']);
        $other = $insert('Autre', ['step' => 'cancelled']);

        $migration = require module_path('Offer', 'database/migrations/2026_11_30_190000_split_booking_requested_by_action.php');
        $migration->up();

        $type = fn (string $id): string => (string) DB::table('notifications')->where('id', $id)->value('type');
        $this->assertSame(BookingRequestCancelled::class, $type($cancelled));
        $this->assertSame(BookingRequestUpdated::class, $type($updated));
        $this->assertSame(BookingRequestReceived::class, $type($noStep));
        $this->assertSame('Autre', $type($other));
        $this->assertSame(['step' => 'cancelled', 'title' => 'Annulée'], json_decode((string) DB::table('notifications')->where('id', $cancelled)->value('data'), true));

        $migration->down();
        $this->assertSame($merged, $type($cancelled));
        $this->assertSame($merged, $type($noStep));
        $this->assertSame('Autre', $type($other));
    }
}
