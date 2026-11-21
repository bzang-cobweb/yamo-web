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
        // 16 du membre, plus trois d'un autre membre : meublé, location, colocation, à la même adresse
        // (décisions 2026-11-21-demo-logement-autre-membre et 2026-11-21-demo-autre-membre-location-colocation).
        $this->assertSame(19, Offer::query()->where('status', 'published')->count());
        $others = Offer::query()->where('user_id', '!=', $member->getKey())->orderBy('id')->get();
        $this->assertSame(['demo-visiteur-1@example.test'], $others->map(fn (Offer $offer) => $offer->user->email)->unique()->values()->all());
        $this->assertSame(['furnished', 'long_term', 'shared'], $others->map(fn (Offer $offer) => $offer->detail('rental_type'))->all());
        $this->assertCount(1, $others->map(fn (Offer $offer) => $offer->offerAddress?->address_id)->unique());
        $others->each(fn (Offer $offer) => $this->assertCount(2, $offer->getMedia(Offer::PHOTOS)));
        $this->assertSame(['XAF'], Offer::query()->distinct()->pluck('currency')->all());
        // Le téléphone et l'e-mail de démonstration de leur propriétaire (décision 2026-11-21-contact-offre).
        $this->assertSame([[DemoSeeder::PHONES[0], DemoSeeder::EMAIL]], Offer::query()->where('user_id', $member->getKey())->get()->map(fn (Offer $offer): array => [$offer->phone, $offer->email])->unique()->values()->all());
        $this->assertSame([[DemoSeeder::PHONES[1], 'demo-visiteur-1@example.test']], $others->map(fn (Offer $offer): array => [$offer->phone, $offer->email])->unique()->values()->all());
        $this->assertSame(5, $member->addresses()->count());
        // Deux photos dessinées par logement, pas plus au second passage.
        $this->assertSame(46, Media::query()->count());
        $this->assertStringStartsWith("\xFF\xD8", Storage::disk('public')->get(Media::query()->first()->getPathRelativeToRoot()));
    }

    public function test_refused_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(LogicException::class);
        $this->seed(DemoSeeder::class);
    }
}
