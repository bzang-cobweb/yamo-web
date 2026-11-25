<?php

namespace Modules\Location\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Location\Database\Seeders\CountrySeeder;
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

    public function test_each_country_gets_its_time_zone_the_capitals_for_a_country_with_several(): void
    {
        // Décision 2026-11-25-fuseau-par-pays.
        $zones = Country::query()->whereIn('code', ['CM', 'CH', 'CI', 'US', 'RU'])->pluck('timezone', 'code')->all();
        $this->assertSame(['CH' => 'Europe/Zurich', 'CI' => 'Africa/Abidjan', 'CM' => 'Africa/Douala', 'RU' => 'Europe/Moscow', 'US' => 'America/New_York'], collect($zones)->sortKeys()->all());
        // Seuls les territoires sans fuseau dans la base de PHP restent vides (Ascension, Tristan da Cunha, Kosovo) :
        // « aujourd'hui » y est celui de l'application.
        $this->assertSame(['AC', 'TA', 'XK'], Country::query()->whereNull('timezone')->orderBy('code')->pluck('code')->all());

        // Changé au back-office, il est gardé au prochain passage du seeder.
        Country::query()->where('code', 'CM')->update(['timezone' => 'Africa/Lagos']);
        $this->seed(CountrySeeder::class);
        $this->assertSame('Africa/Lagos', Country::query()->where('code', 'CM')->value('timezone'));
    }

    public function test_native_names_come_from_the_country_language(): void
    {
        $natives = Country::query()->whereIn('code', ['DE', 'JP', 'ES', 'CH'])->pluck('native_name', 'code');

        $this->assertSame('Deutschland', $natives['DE']);
        $this->assertSame('日本', $natives['JP']);
        $this->assertSame('España', $natives['ES']);
        $this->assertSame('Schweiz', $natives['CH']);
    }

    public function test_seeds_the_regions_of_each_country_with_a_data_file_and_their_name(): void
    {
        // Décision 2026-11-25-regions-par-pays : 26 cantons, 10 régions, 14 districts ; un pays sans fichier n'en a pas.
        $counts = Country::query()->whereIn('code', ['CH', 'CM', 'CI', 'SN'])->withCount('regions')->get()
            ->mapWithKeys(fn (Country $country): array => [$country->code => (int) $country->regions_count])->sortKeys()->all();
        $this->assertSame(['CH' => 26, 'CI' => 14, 'CM' => 10, 'SN' => 0], $counts);
        $this->assertSame(50, Region::count());

        // Le nom des régions, traduit ; sans fichier, « Région ».
        $countries = Country::query()->whereIn('code', ['CH', 'CM', 'CI', 'SN'])->get()->keyBy('code');
        $this->assertSame(['fr' => 'Canton', 'en' => 'Canton', 'de' => 'Kanton'], $countries['CH']->getTranslations('region_label'));
        $this->assertSame('District', $countries['CI']->regionLabel());
        $this->assertSame('Région', $countries['CM']->regionLabel());
        $this->assertSame('Région', $countries['SN']->regionLabel());
        $this->assertSame('Far North', Region::query()->where('country_id', $countries['CM']->getKey())->where('name->fr', 'Extrême-Nord')->firstOrFail()->getTranslation('name', 'en'));

        $switzerland = $countries['CH'];

        $geneva = $switzerland->regions()->where('name->fr', 'Genève')->firstOrFail();
        $this->assertSame('Geneva', $geneva->getTranslation('name', 'en'));
        $this->assertSame('Genf', $geneva->getTranslation('name', 'de'));
        $this->assertTrue($geneva->active);
    }

    public function test_reseeding_duplicates_nothing_and_keeps_back_office_changes(): void
    {
        $countries = Country::count();
        $switzerland = Country::query()->where('code', 'CH')->firstOrFail();
        $switzerland->update(['active' => false, 'dial_code' => '+4199']);
        $switzerland->setTranslations('region_label', ['fr' => 'Kanton', 'en' => 'Kanton', 'de' => 'Kanton'])->save();
        $canton = $switzerland->regions()->firstOrFail();
        $canton->update(['active' => false, 'position' => 99]);
        // Une région saisie à la main avec une espace en trop est reprise, pas doublée, et son nom corrigé.
        $cameroon = Country::query()->where('code', 'CM')->firstOrFail();
        $farNorth = Region::query()->where('country_id', $cameroon->getKey())->where('name->fr', 'Extrême-Nord')->firstOrFail();
        $farNorth->setTranslations('name', ['fr' => ' Extrême-Nord'])->save();

        $this->seed(LocationDatabaseSeeder::class);

        $this->assertSame($countries, Country::count());
        $this->assertSame(50, Region::count());
        $this->assertSame('Extrême-Nord', $farNorth->refresh()->getTranslation('name', 'fr'));
        // Le nom des régions réglé au back-office est gardé.
        $this->assertSame('Kanton', $switzerland->refresh()->getTranslation('region_label', 'fr'));
        $this->assertSame(99, $canton->refresh()->position);
        $this->assertFalse($switzerland->refresh()->active);
        $this->assertSame('+4199', $switzerland->dial_code);
        $this->assertFalse($canton->refresh()->active);
    }
}
