<?php

namespace Modules\Partner\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Offer\Models\Offer;
use Modules\Partner\Models\Partner;
use Tests\TestCase;

/**
 * Le slug `url` traduisible des offres et des partenaires (décision 2026-11-25-slug-url-offres-partenaires).
 */
class PartnerSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_partner_gets_one_unique_slug_per_language_from_its_name(): void
    {
        $first = Partner::factory()->create(['name' => ['fr' => 'Déménageurs du Wouri', 'en' => 'Wouri Movers']]);
        $second = Partner::factory()->create(['name' => ['fr' => 'Déménageurs du Wouri']]);

        $this->assertSame(['fr' => 'demenageurs-du-wouri', 'en' => 'wouri-movers'], $first->getTranslations('url'));
        $this->assertSame(['fr' => 'demenageurs-du-wouri-1'], $second->getTranslations('url'));
    }

    public function test_renaming_a_partner_follows_with_its_slug(): void
    {
        $partner = Partner::factory()->create(['name' => ['fr' => 'Meubles Akwa']]);

        $partner->setTranslation('name', 'fr', 'Meubles Bonanjo')->save();

        $this->assertSame('meubles-bonanjo', $partner->getTranslation('url', 'fr'));
    }

    public function test_an_offer_gets_its_slug_from_its_title(): void
    {
        $offer = Offer::factory()->create(['title' => ['fr' => 'Studio à Bonapriso', 'en' => 'Studio in Bonapriso']]);

        $this->assertSame(['fr' => 'studio-a-bonapriso', 'en' => 'studio-in-bonapriso'], $offer->getTranslations('url'));
    }
}
