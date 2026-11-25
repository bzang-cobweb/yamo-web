<?php

namespace Modules\Offer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Offer\Models\OfferCategory;

/**
 * @extends Factory<OfferCategory>
 */
class OfferCategoryFactory extends Factory
{
    protected $model = OfferCategory::class;

    public function definition(): array
    {
        $name = 'Catégorie '.fake()->unique()->numberBetween(1, 1000000);

        // Le slug, traduit, est tiré du nom (décision 2026-11-25-categories-slug-traduit).
        return [
            'name' => ['fr' => $name, 'en' => $name],
            'position' => 0,
        ];
    }
}
