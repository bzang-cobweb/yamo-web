<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Database\Seeders\OfferDatabaseSeeder;
use Modules\Offer\Models\Offer;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * Données de démonstration : un membre camerounais et 20 logements, rejouable,
 * refusé en production.
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['offer.domain' => 'rental']);
        Country::factory()->create(['code' => 'CM', 'currency' => 'XAF']);
        $this->seed(OfferDatabaseSeeder::class);
    }

    public function test_a_member_with_20_offers_in_cameroon_and_replaying_adds_nothing(): void
    {
        Storage::fake('public');
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $member = User::query()->where('email', DemoSeeder::EMAIL)->sole();
        $this->assertSame(['member'], $member->getRoleNames()->all());
        $this->assertSame(20, Offer::query()->where('user_id', $member->getKey())->count());
        $this->assertSame(16, Offer::query()->where('status', 'published')->count());
        $this->assertSame(['XAF'], Offer::query()->distinct()->pluck('currency')->all());
        $this->assertSame(5, $member->addresses()->count());
        // Deux photos dessinées par logement, pas plus au second passage.
        $this->assertSame(40, Media::query()->count());
        $this->assertStringStartsWith("\xFF\xD8", Storage::disk('public')->get(Media::query()->first()->getPathRelativeToRoot()));
    }

    public function test_refused_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(LogicException::class);
        $this->seed(DemoSeeder::class);
    }
}
