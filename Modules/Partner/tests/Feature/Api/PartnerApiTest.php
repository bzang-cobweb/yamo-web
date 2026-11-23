<?php

namespace Modules\Partner\Tests\Feature\Api;

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

    public function test_only_visible_partners_of_the_country_in_their_order_with_their_texts_in_the_language(): void
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

        $this->assertSame([(string) $first->getKey(), (string) $second->getKey(), (string) $running->getKey()], array_column($data, 'id'));
        $this->assertSame([
            'id' => (string) $first->getKey(),
            'name' => 'Déménageurs du Wouri',
            'description' => 'Déménagement clés en main.',
            'url' => 'https://wouri.test/fr',
            'cta_label' => 'Demander un devis',
            'logo' => null,
        ], $data[0]);
        // Ni contact ni compteurs dans l'app.
        $this->assertArrayNotHasKey('contact_email', $data[0]);
        $this->assertArrayNotHasKey('views_count', $data[0]);

        // Un nom et un lien par langue.
        $this->assertSame(['Wouri Movers', 'https://wouri.test/en'], [$this->partners('en')->json('data.0.name'), $this->partners('en')->json('data.0.url')]);
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

    public function test_no_partner_an_empty_list(): void
    {
        $this->partners()->assertOk()->assertExactJson(['data' => []]);
    }
}
