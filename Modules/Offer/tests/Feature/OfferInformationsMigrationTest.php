<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Offer\Models\Offer;
use Tests\TestCase;

/**
 * La migration des blocs d'informations (décision 2026-11-19-contrat-offer-informations) :
 * un texte « Autres informations » devient un bloc et quitte `details` ; le retour
 * arrière remet le premier bloc en texte.
 */
class OfferInformationsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_other_information_becomes_a_block_and_back(): void
    {
        $migration = require module_path('Offer', 'database/migrations/2026_11_29_140000_create_offer_informations_table.php');
        $migration->down();
        $with = Offer::factory()->create(['details' => ['property_type' => 'studio']]);
        $empty = Offer::factory()->create(['details' => ['property_type' => 'room']]);
        DB::table('offers')->where('id', $with->getKey())->update(['details' => json_encode(['property_type' => 'studio', 'other_information' => ['fr' => 'Clés chez le gardien.', 'en' => 'Keys with the caretaker.']])]);
        DB::table('offers')->where('id', $empty->getKey())->update(['details' => json_encode(['property_type' => 'room', 'other_information' => ['fr' => '  ']])]);

        $migration->up();

        $block = $with->fresh()->informations->sole();
        $this->assertSame(['fr' => 'Autres informations', 'en' => 'Other information'], $block->getTranslations('title'));
        $this->assertSame(['fr' => 'Clés chez le gardien.', 'en' => 'Keys with the caretaker.'], $block->getTranslations('description'));
        // Le texte quitte `details`, les autres attributs restent ; un texte vide ne donne pas de bloc.
        $this->assertSame(['property_type' => 'studio'], $with->fresh()->details);
        $this->assertSame(['property_type' => 'room'], $empty->fresh()->details);
        $this->assertCount(0, $empty->fresh()->informations);

        $migration->down();
        $this->assertSame(['fr' => 'Clés chez le gardien.', 'en' => 'Keys with the caretaker.'], json_decode((string) DB::table('offers')->where('id', $with->getKey())->value('details'), true)['other_information']);
        $migration->up();
    }
}
