<?php

namespace Modules\Partner\Tests\Feature\Api;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Location\Models\Country;
use Modules\Partner\Models\Partner;
use Tests\TestCase;

/**
 * Les partenaires de l'accueil, sans compte (décision 2026-11-23-module-partenaires).
 */
class PartnerApiTest extends TestCase
{
    use RefreshDatabase;

    private Country $cameroon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cameroon = Country::factory()->create(['code' => 'CM']);
    }

    private function partners(string $locale = 'fr')
    {
        return $this->withHeaders(['Accept-Language' => $locale, 'X-Country' => 'CM'])->getJson('/api/v1/partners');
    }

    public function test_only_visible_partners_of_the_country_with_their_texts_in_the_language(): void
    {
        $second = Partner::factory()->for($this->cameroon)->create(['name' => ['fr' => 'Second'], 'position' => 2]);
        $first = Partner::factory()->for($this->cameroon)->create([
            'name' => ['fr' => 'Déménageurs du Wouri', 'en' => 'Wouri Movers'],
            'position' => 1,
            'description' => ['fr' => 'Déménagement clés en main.', 'en' => 'Turnkey moving.'],
            'url' => ['fr' => 'https://wouri.test/fr', 'en' => 'https://wouri.test/en'],
            'cta_label' => ['fr' => 'Demander un devis', 'en' => 'Get a quote'],
            'contact_email' => 'commercial@wouri.test',
        ]);
        // Pas publié, campagne pas commencée, campagne finie, autre pays : absents.
        Partner::factory()->for($this->cameroon)->create(['published' => false]);
        Partner::factory()->for($this->cameroon)->create(['starts_at' => now()->addDay()]);
        Partner::factory()->for($this->cameroon)->create(['expires_at' => now()->subMinute()]);
        Partner::factory()->create();
        // Campagne en cours : présent.
        $running = Partner::factory()->for($this->cameroon)->create(['position' => 3, 'starts_at' => now()->subDay(), 'expires_at' => now()->addDay()]);

        $data = $this->partners()->assertOk()->json('data');

        // Au hasard (décision 2026-11-23-accueil-nombres-reglables) : les visibles, dans n'importe quel ordre.
        $this->assertEqualsCanonicalizing([(string) $first->getKey(), (string) $second->getKey(), (string) $running->getKey()], array_column($data, 'id'));
        $data = collect($data)->keyBy('id')->all();
        $this->assertSame([
            'id' => (string) $first->getKey(),
            'name' => 'Déménageurs du Wouri',
            'description' => 'Déménagement clés en main.',
            'url' => 'https://wouri.test/fr',
            'cta_label' => 'Demander un devis',
            'logo' => null,
        ], $data[(string) $first->getKey()]);
        // Ni contact ni compteurs dans l'app.
        $this->assertArrayNotHasKey('contact_email', $data[(string) $first->getKey()]);
        $this->assertArrayNotHasKey('views_count', $data[(string) $first->getKey()]);

        // Un nom et un lien par langue.
        $english = collect($this->partners('en')->json('data'))->firstWhere('id', (string) $first->getKey());
        $this->assertSame(['Wouri Movers', 'https://wouri.test/en'], [$english['name'], $english['url']]);
    }

    public function test_each_listing_counts_a_view_and_each_touch_a_click(): void
    {
        $partner = Partner::factory()->for($this->cameroon)->create();
        $hidden = Partner::factory()->for($this->cameroon)->create(['published' => false]);

        $this->partners()->assertOk();
        $this->partners()->assertOk();
        $this->withHeader('X-Country', 'CM')->postJson("/api/v1/partners/{$partner->getKey()}/click")->assertNoContent();

        $this->assertSame([2, 1], [$partner->fresh()->views_count, $partner->fresh()->clicks_count]);
        // Un partenaire invisible ne compte rien.
        $this->withHeader('X-Country', 'CM')->postJson("/api/v1/partners/{$hidden->getKey()}/click")->assertNotFound();
        $this->assertSame([0, 0], [$hidden->fresh()->views_count, $hidden->fresh()->clicks_count]);
    }

    public function test_as_many_partners_as_set_in_the_application_drawn_at_random(): void
    {
        // Décision 2026-11-23-accueil-nombres-reglables : 10 sans réglage, sinon le nombre réglé.
        Partner::factory()->count(12)->for($this->cameroon)->create();
        $this->assertCount(10, $this->partners()->json('data'));

        Setting::current()->update(['home_partners' => 3]);
        $draws = collect(range(1, 6))->map(fn (): array => array_column($this->partners()->json('data'), 'id'));
        $draws->each(fn (array $ids) => $this->assertCount(3, $ids));
        // Le tirage change d'un appel à l'autre.
        $this->assertGreaterThan(1, $draws->map(fn (array $ids): string => implode(',', $ids))->unique()->count());
        // Seuls les partenaires servis comptent une vue.
        $this->assertSame(10 + 6 * 3, (int) Partner::query()->sum('views_count'));
    }

    public function test_no_partner_an_empty_list(): void
    {
        $this->partners()->assertOk()->assertExactJson(['data' => []]);
    }
}
