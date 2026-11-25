<?php

namespace Modules\Offer\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        // La migration rejouée date d'avant le slug traduit (décision 2026-11-25-categories-slug-traduit) : la base est
        // remise à ce moment-là, puis remise à jour.
        $slugs = require base_path('Modules/Offer/database/migrations/2026_12_07_230000_translate_offer_category_slugs.php');
        $flags = require base_path('Modules/Offer/database/migrations/2026_12_07_240000_store_offer_flags_by_category_id.php');
        $flags->down();
        $slugs->down();

        $category = fn (string $slug, string $name, ?int $parent = null): object => DB::table('offer_categories')->find(DB::table('offer_categories')->insertGetId(['slug' => $slug, 'name' => json_encode(['fr' => $name]), 'position' => 0, 'parent_id' => $parent]));
        $longTerm = $category('long-term', 'Location longue durée');
        $furnished = $category('furnished', 'Location meublée');
        $shared = $category('shared', 'Colocation');
        $other = $category('standing', 'Standing');
        $child = $category('luxe', 'Luxe', $shared->id);

        $offer = fn (?object $in, ?bool $isFurnished): Offer => Offer::factory()->create([
            'category_id' => $in?->id,
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
        $this->assertSame(['furnished', null, $other->id], $type($d));
        $this->assertSame(['long_term', null, null], $type($e));

        $this->assertSame(['standing', 'luxe'], DB::table('offer_categories')->orderBy('id')->pluck('slug')->all());
        $this->assertNull(DB::table('offer_categories')->where('id', $child->id)->value('parent_id'));

        $slugs->up();
        $flags->up();
        $this->assertSame(['fr' => 'standing'], array_intersect_key(OfferCategory::query()->findOrFail($other->id)->getTranslations('slug'), ['fr' => 1]));
    }
}
