<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;
use Tests\TestCase;

/**
 * `booking:reset` : vide les demandes et les notifications, puis remet les demandes de
 * démonstration (décision 2026-11-20-reset-booking).
 */
class ResetBookingsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
    }

    public function test_it_empties_the_bookings_and_notifications_and_puts_back_the_demo_requests(): void
    {
        $member = User::query()->where('email', DemoSeeder::EMAIL)->sole();
        $demo = Booking::query()->pluck('id')->all();
        $this->assertCount(11, $demo);
        // Une demande et une notification hors démo : effacées aussi.
        $other = Booking::factory()->for(Offer::factory()->create())->create();
        $member->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'Autre', 'data' => ['title' => 'Autre', 'body' => 'Texte']]);

        $this->artisan('booking:reset', ['--force' => true])->assertSuccessful();

        $this->assertSame(11, Booking::query()->count());
        $this->assertSame([], array_intersect([...$demo, $other->getKey()], Booking::query()->pluck('id')->all()));
        // Des réponses du propriétaire lues par leur visiteur (décision 2026-11-20-demo-reponses-lues),
        // pas toutes : on voit les coches vertes et les grises.
        $answers = DatabaseNotification::query()->whereNotNull('user_id')
            ->whereIn('notifiable_id', Booking::query()->pluck('user_id'))
            ->whereColumn('user_id', '!=', 'notifiable_id')->get();
        $this->assertGreaterThanOrEqual(3, $answers->whereNotNull('read_at')->count());
        $this->assertNotEmpty($answers->whereNull('read_at'));
        $this->assertSame(0, DatabaseNotification::query()->where('type', 'Autre')->count());
        // Chaque demande recréée a ses notifications, au membre (propriétaire) et aux visiteurs.
        $this->assertGreaterThan(11, DatabaseNotification::query()->count());
        $this->assertSame(11, $member->notifications()->distinct()->count('thread'));
        // Le reste n'est pas touché.
        $this->assertSame(20, Offer::query()->where('user_id', $member->getKey())->count());
    }

    public function test_without_confirmation_nothing_changes(): void
    {
        $this->artisan('booking:reset')
            ->expectsConfirmation('Supprimer 11 demandes et '.DatabaseNotification::query()->count().' notifications, puis remettre les demandes de démonstration ?', 'no')
            ->assertSuccessful();

        $this->assertSame(11, Booking::query()->count());
    }

    public function test_refused_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('booking:reset', ['--force' => true])->assertFailed();
        $this->assertSame(11, Booking::query()->count());
    }
}
