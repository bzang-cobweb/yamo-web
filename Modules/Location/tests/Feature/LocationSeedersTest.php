<?php

namespace Modules\Location\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Location\Database\Seeders\LocationDatabaseSeeder;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;
use Tests\TestCase;

class LocationSeedersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LocationDatabaseSeeder::class);
    }

    public function test_seeds_every_country_active_with_its_dial_code(): void
    {
        $this->assertGreaterThanOrEqual(240, Country::count());
        $this->assertSame(Country::count(), Country::active()->count());

        $switzerland = Country::query()->where('code', 'CH')->firstOrFail();
        $this->assertSame('+41', $switzerland->dial_code);
        $this->assertSame('Suisse', $switzerland->getTranslation('name', 'fr'));
        $this->assertSame('Switzerland', $switzerland->getTranslation('name', 'en'));
        $this->assertNotSame('', $switzerland->native_name);
    }

    public function test_native_names_come_from_the_country_language(): void
    {
        $natives = Country::query()->whereIn('code', ['DE', 'JP', 'ES', 'CH'])->pluck('native_name', 'code');

        $this->assertSame('Deutschland', $natives['DE']);
        $this->assertSame('日本', $natives['JP']);
        $this->assertSame('España', $natives['ES']);
        $this->assertSame('Schweiz', $natives['CH']);
    }

    public function test_seeds_the_26_swiss_cantons(): void
    {
        $switzerland = Country::query()->where('code', 'CH')->firstOrFail();

        $this->assertSame(26, Region::count());
        $this->assertSame(26, $switzerland->regions()->count());

        $geneva = $switzerland->regions()->where('name->fr', 'Genève')->firstOrFail();
        $this->assertSame('Geneva', $geneva->getTranslation('name', 'en'));
        $this->assertTrue($geneva->active);
    }

    public function test_reseeding_duplicates_nothing_and_keeps_back_office_changes(): void
    {
        $countries = Country::count();
        $switzerland = Country::query()->where('code', 'CH')->firstOrFail();
        $switzerland->update(['active' => false, 'dial_code' => '+4199']);
        $canton = $switzerland->regions()->firstOrFail();
        $canton->update(['active' => false]);

        $this->seed(LocationDatabaseSeeder::class);

        $this->assertSame($countries, Country::count());
        $this->assertSame(26, Region::count());
        $this->assertFalse($switzerland->refresh()->active);
        $this->assertSame('+4199', $switzerland->dial_code);
        $this->assertFalse($canton->refresh()->active);
    }
}
