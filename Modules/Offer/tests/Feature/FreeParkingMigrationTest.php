<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Offer\Models\OfferCategory;
use Tests\TestCase;

/**
 * « Parking gratuit » ajouté aux « Équipements » existants, juste après « Parking » (décision 2026-11-26-parking-gratuit).
 */
class FreeParkingMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_parking_is_inserted_after_parking_once(): void
    {
        $root = OfferCategory::query()->create(['name' => ['fr' => 'Équipements', 'en' => 'Amenities'], 'position' => 0]);
        foreach (['Wifi', 'Parking', 'Climatisation'] as $position => $name) {
            OfferCategory::query()->create(['name' => ['fr' => $name, 'en' => $name], 'parent_id' => $root->getKey(), 'position' => $position]);
        }
        $migration = require base_path('Modules/Offer/database/migrations/2026_12_08_100000_add_free_parking_equipment.php');

        $migration->up();
        $migration->up();

        $names = fn (): array => $root->children()->orderBy('position')->get()->map(fn (OfferCategory $category): string => (string) $category->getTranslation('name', 'fr'))->all();
        $this->assertSame(['Wifi', 'Parking', 'Parking gratuit', 'Climatisation'], $names());

        $migration->down();
        $this->assertSame(['Wifi', 'Parking', 'Climatisation'], $names());
    }
}
