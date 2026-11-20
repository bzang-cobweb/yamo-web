<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Offer\Notifications\BookingRequested;
use Tests\TestCase;

/**
 * Les quatre notifications d'une demande deviennent `BookingRequested`, avec l'étape
 * dans `data.step` (décision 2026-11-20-notification-demande-unique).
 */
class BookingRequestedMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_booking_notifications_become_one_type_with_their_step(): void
    {
        $user = User::factory()->create();
        $insert = fn (string $type, array $data) => DB::table('notifications')->insert([
            'id' => $id = (string) Str::uuid(), 'type' => $type, 'notifiable_type' => $user->getMorphClass(), 'notifiable_id' => $user->getKey(),
            'data' => json_encode($data), 'created_at' => now(), 'updated_at' => now(),
        ]) ? $id : '';
        $received = $insert('Modules\\Offer\\Notifications\\BookingRequestReceived', ['title' => 'Reçue', 'booking_id' => 4]);
        $refused = $insert('Modules\\Offer\\Notifications\\BookingRequestRefused', ['title' => 'Refusée']);
        $other = $insert('Autre', ['title' => 'Autre']);

        $migration = require module_path('Offer', 'database/migrations/2026_11_30_130000_merge_booking_notifications_into_booking_requested.php');
        $migration->up();

        $row = fn (string $id): object => DB::table('notifications')->where('id', $id)->first();
        $this->assertSame(BookingRequested::class, $row($received)->type);
        $this->assertSame(['step' => 'received', 'title' => 'Reçue', 'booking_id' => 4], json_decode($row($received)->data, true));
        $this->assertSame('refused', json_decode($row($refused)->data, true)['step']);
        $this->assertSame('Autre', $row($other)->type);

        $migration->down();
        $this->assertSame('Modules\\Offer\\Notifications\\BookingRequestRefused', $row($refused)->type);
        $this->assertSame(['title' => 'Refusée'], json_decode($row($refused)->data, true));
    }
}
