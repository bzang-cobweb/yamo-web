<?php

namespace Modules\Partner\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Location\Models\Country;
use Modules\Offer\Models\Offer;
use Modules\Partner\Models\Partner;
use Tests\TestCase;

/**
 * Le slug `url` traduisible des offres et des partenaires (décision 2026-11-25-slug-url-offres-partenaires).
 */
class PartnerSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_partner_slug_is_unique_per_country_and_language(): void
    {
        [$cameroon, $gabon] = [Country::factory()->create(['code' => 'CM']), Country::factory()->create(['code' => 'GA'])];
        $first = Partner::factory()->create(['country_id' => $cameroon->getKey(), 'name' => ['fr' => 'Déménageurs du Wouri', 'en' => 'Wouri Movers']]);
        $sameCountry = Partner::factory()->create(['country_id' => $cameroon->getKey(), 'name' => ['fr' => 'Déménageurs du Wouri', 'en' => 'Demenageurs du Wouri']]);
        $otherCountry = Partner::factory()->create(['country_id' => $gabon->getKey(), 'name' => ['fr' => 'Déménageurs du Wouri']]);

        $this->assertSame(['fr' => 'demenageurs-du-wouri', 'en' => 'wouri-movers'], $first->getTranslations('url'));
        // Le même slug dans une autre langue reste libre ; dans la même langue et le même pays, il prend un suffixe.
        $this->assertSame(['fr' => 'demenageurs-du-wouri-1', 'en' => 'demenageurs-du-wouri'], $sameCountry->getTranslations('url'));
        $this->assertSame(['fr' => 'demenageurs-du-wouri'], $otherCountry->getTranslations('url'));
    }

    public function test_renaming_a_partner_follows_with_its_slug(): void
    {
        $partner = Partner::factory()->create(['name' => ['fr' => 'Meubles Akwa']]);

        $partner->setTranslation('name', 'fr', 'Meubles Bonanjo')->save();

        $this->assertSame('meubles-bonanjo', $partner->getTranslation('url', 'fr'));
    }

    public function test_an_offer_slug_comes_from_its_title_unique_per_country_and_language(): void
    {
        [$cameroon, $gabon] = [Country::factory()->create(['code' => 'CM']), Country::factory()->create(['code' => 'GA'])];
        $offer = Offer::factory()->create(['country_id' => $cameroon->getKey(), 'title' => ['fr' => 'Studio à Bonapriso', 'en' => 'Studio in Bonapriso']]);
        $sameCountry = Offer::factory()->create(['country_id' => $cameroon->getKey(), 'title' => ['fr' => 'Studio à Bonapriso']]);
        $otherCountry = Offer::factory()->create(['country_id' => $gabon->getKey(), 'title' => ['fr' => 'Studio à Bonapriso']]);

        $this->assertSame(['fr' => 'studio-a-bonapriso', 'en' => 'studio-in-bonapriso'], $offer->getTranslations('url'));
        $this->assertSame(['fr' => 'studio-a-bonapriso-1'], $sameCountry->getTranslations('url'));
        $this->assertSame(['fr' => 'studio-a-bonapriso'], $otherCountry->getTranslations('url'));
    }
}
