<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferCategory;
use Tests\TestCase;

/**
 * Les trois catégories de location et « Meublé » passent dans le type de location
 * (décisions 2026-11-20-contrat-type-de-location et 2026-11-20-type-de-location-garder-categories).
 */
class RentalTypeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_and_furnished_move_to_the_rental_type_and_other_categories_stay(): void
    {
        $category = fn (string $slug, string $name): OfferCategory => OfferCategory::query()->create(['slug' => $slug, 'name' => ['fr' => $name], 'position' => 0]);
        $longTerm = $category('long-term', 'Location longue durée');
        $furnished = $category('furnished', 'Location meublée');
        $shared = $category('shared', 'Colocation');
        $other = $category('standing', 'Standing');
        $child = OfferCategory::query()->create(['slug' => 'luxe', 'name' => ['fr' => 'Luxe'], 'parent_id' => $shared->getKey(), 'position' => 0]);

        $offer = fn (?OfferCategory $in, ?bool $isFurnished): Offer => Offer::factory()->create([
            'category_id' => $in?->getKey(),
            'details' => array_filter(['property_type' => 'studio', 'furnished' => $isFurnished], fn ($value) => $value !== null),
        ]);
        $a = $offer($longTerm, false);
        $b = $offer($furnished, true);
        // Une colocation cochée « Meublé » : la catégorie l'emporte.
        $c = $offer($shared, true);
        $d = $offer($other, true);
        $e = $offer(null, false);

        (require base_path('Modules/Offer/database/migrations/2026_11_30_120000_move_furnished_and_categories_to_rental_type.php'))->up();

        $type = fn (Offer $offer): array => [$offer->fresh()->detail('rental_type'), $offer->fresh()->detail('furnished'), $offer->fresh()->category_id];
        $this->assertSame(['long_term', null, null], $type($a));
        $this->assertSame(['furnished', null, null], $type($b));
        $this->assertSame(['shared', null, null], $type($c));
        // Une autre catégorie reste ; sans catégorie de location, « Meublé » décide.
        $this->assertSame(['furnished', null, $other->getKey()], $type($d));
        $this->assertSame(['long_term', null, null], $type($e));

        $this->assertSame(['standing', 'luxe'], OfferCategory::query()->orderBy('id')->pluck('slug')->all());
        $this->assertNull($child->fresh()->parent_id);
    }
}
