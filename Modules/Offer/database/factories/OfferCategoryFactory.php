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
        $slug = 'categorie-'.fake()->unique()->numberBetween(1, 1000000);

        return [
            'name' => ['fr' => ucfirst($slug), 'en' => ucfirst($slug)],
            'slug' => $slug,
            'position' => 0,
        ];
    }
}
