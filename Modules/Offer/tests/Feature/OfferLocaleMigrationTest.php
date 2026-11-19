<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Location\Models\Address;
use Modules\Offer\Models\Offer;
use Tests\TestCase;

/**
 * La langue d'origine des offres existantes (décision 2026-11-19-contrat-langue-saisie-allemand) :
 * le français si leur titre l'a, sinon la première langue renseignée.
 */
class OfferLocaleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_offers_get_french_or_their_first_title_language(): void
    {
        $migration = require module_path('Offer', 'database/migrations/2026_11_29_150000_add_locale_to_offers.php');
        $migration->down();
        $french = Offer::factory()->create();
        $english = Offer::factory()->create();
        DB::table('offers')->where('id', $french->getKey())->update(['title' => json_encode(['en' => 'Flat', 'fr' => 'Appartement'])]);
        DB::table('offers')->where('id', $english->getKey())->update(['title' => json_encode(['de' => '', 'en' => 'House'])]);

        $migration->up();

        $this->assertSame('fr', DB::table('offers')->where('id', $french->getKey())->value('locale'));
        $this->assertSame('en', DB::table('offers')->where('id', $english->getKey())->value('locale'));
    }

    public function test_an_existing_indication_moves_into_the_offer_original_language(): void
    {
        // Décision 2026-11-19-indication-traduisible.
        $migration = require module_path('Offer', 'database/migrations/2026_11_29_170000_make_offer_address_indication_translatable.php');
        $migration->down();
        $english = Offer::factory()->at(Address::factory()->create())->create(['locale' => 'en']);
        $unknown = Offer::factory()->at(Address::factory()->create())->create(['locale' => null]);
        DB::table('offer_addresses')->where('offer_id', $english->getKey())->update(['indication' => 'Blue gate']);
        DB::table('offer_addresses')->where('offer_id', $unknown->getKey())->update(['indication' => 'Portail vert']);

        $migration->up();

        $this->assertSame(['en' => 'Blue gate'], $english->fresh()->offerAddress->getTranslations('indication'));
        $this->assertSame(['fr' => 'Portail vert'], $unknown->fresh()->offerAddress->getTranslations('indication'));

        $migration->down();
        $this->assertSame('Blue gate', DB::table('offer_addresses')->where('offer_id', $english->getKey())->value('indication'));
        $migration->up();
    }
}
