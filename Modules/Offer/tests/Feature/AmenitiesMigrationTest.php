<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;
use Tests\TestCase;

/**
 * Les anciens équipements deviennent des cases de « Équipements » (décision 2026-11-25-equipements-en-categories).
 */
class AmenitiesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_amenity_codes_become_category_ids_created_when_needed_in_order(): void
    {
        $offer = Offer::factory()->create(['details' => ['property_type' => 'studio', 'amenities' => ['wifi', 'pool']]]);
        $without = Offer::factory()->create(['details' => ['property_type' => 'studio']]);
        $this->assertSame(0, OfferCategory::query()->count());

        (require base_path('Modules/Offer/database/migrations/2026_12_07_260000_move_amenities_to_equipment_categories.php'))->up();

        $id = fn (string $name): string => (string) OfferCategory::query()->whereNotNull('parent_id')->where('name->fr', $name)->value('id');
        $this->assertSame([$id('Wifi'), $id('Piscine')], $offer->fresh()->detail('feature_flags'));
        $this->assertNull($offer->fresh()->detail('amenities'));
        $this->assertNull($without->fresh()->detail('feature_flags'));
        // « Équipements » et ses cases, dans l'ordre : les anciens équipements, puis le groupe électrogène.
        $root = OfferCategory::query()->whereNull('parent_id')->sole();
        $this->assertSame('Équipements', $root->getTranslation('name', 'fr'));
        $names = $root->children()->orderBy('position')->get()->map(fn (OfferCategory $category): string => (string) $category->getTranslation('name', 'fr'))->all();
        $this->assertSame(['Wifi', 'Parking'], array_slice($names, 0, 2));
        $this->assertSame('Groupe électrogène', end($names));
    }
}
