<?php

namespace Modules\Offer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;

/**
 * @extends Factory<OfferReview>
 */
class OfferReviewFactory extends Factory
{
    protected $model = OfferReview::class;

    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->randomFloat(1, 1, 5),
            'comment' => fake()->sentence(),
        ];
    }
}
