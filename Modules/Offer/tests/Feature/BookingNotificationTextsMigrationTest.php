<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Modules\Offer\Notifications\BookingRequestCancelled;
use Tests\TestCase;

/**
 * Les notifications de demande enregistrées prennent les textes en vigueur (décision 2026-11-22-reecrire-textes-notifications).
 */
class BookingNotificationTextsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stored_booking_notifications_take_the_current_texts(): void
    {
        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
        $owner = User::factory()->create(['locale' => 'fr']);
        $guest = User::factory()->create(['locale' => 'fr']);
        $offer = Offer::factory()->for($owner, 'user')->create(['details' => ['property_type' => 'studio', 'rental_type' => 'furnished']]);
        $booking = Booking::factory()->for($offer)->for($guest)->create(['type' => 'visit', 'check_in' => '2027-03-03', 'check_out' => null, 'cancel_message' => 'Je ne viens plus']);
        $insert = fn (User $to, ?User $actor, array $data) => DB::table('notifications')->insert([
            'id' => $id = (string) Str::uuid(), 'type' => BookingRequestCancelled::class, 'notifiable_type' => $to->getMorphClass(), 'notifiable_id' => $to->getKey(),
            'user_id' => $actor?->getKey(), 'data' => json_encode(['booking_id' => $booking->getKey(), 'body' => 'Ancien', 'summary' => 'Ancien', 'message' => 'Gardé', ...$data]),
            'created_at' => now(), 'updated_at' => now(),
        ]) ? $id : '';
        $sent = $insert($owner, $guest, ['own' => false]);
        $own = $insert($guest, null, ['own' => true]);
        $legacyOwn = $insert($guest, null, []);
        $missing = $insert($owner, $guest, ['booking_id' => 999999]);

        $migration = require module_path('Offer', 'database/migrations/2026_11_30_200000_rewrite_booking_notification_texts.php');
        $migration->up();

        $data = fn (string $id): array => json_decode((string) DB::table('notifications')->where('id', $id)->value('data'), true);
        $this->assertSame('La demande de visite pour « '.$offer->getTranslation('title', 'fr').' » (le 03/03/2027) a été annulée.', $data($sent)['summary']);
        $this->assertStringContainsString(' a annulé sa demande de visite pour « ', $data($sent)['body']);
        $this->assertSame('Gardé', $data($sent)['message']);
        $this->assertStringStartsWith('Vous avez annulé votre demande de visite pour « ', $data($own)['body']);
        $this->assertNull($data($own)['summary']);
        $this->assertStringStartsWith('Vous avez annulé votre demande de visite', $data($legacyOwn)['body']);
        $this->assertSame('Ancien', $data($missing)['body']);
    }
}
