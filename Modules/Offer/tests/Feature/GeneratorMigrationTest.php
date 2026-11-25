<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Offer\Models\Offer;
use Tests\TestCase;

/**
 * Le groupe électrogène quitte les équipements pour une caractéristique Oui / Non (décision
 * 2026-11-25-oui-non-trois-positions).
 */
class GeneratorMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_ticked_generator_becomes_yes_and_leaves_the_amenities_and_back(): void
    {
        $with = Offer::factory()->create(['details' => ['property_type' => 'studio', 'amenities' => ['wifi', 'generator']]]);
        $without = Offer::factory()->create(['details' => ['property_type' => 'studio', 'amenities' => ['wifi']]]);
        $migration = require base_path('Modules/Offer/database/migrations/2026_12_07_220000_move_generator_out_of_amenities.php');

        $migration->up();
        $this->assertSame(['wifi'], $with->fresh()->detail('amenities'));
        $this->assertSame('yes', $with->fresh()->detail('generator'));
        $this->assertNull($without->fresh()->detail('generator'));

        $migration->down();
        $this->assertSame(['wifi', 'generator'], $with->fresh()->detail('amenities'));
        $this->assertNull($with->fresh()->detail('generator'));
    }
}
