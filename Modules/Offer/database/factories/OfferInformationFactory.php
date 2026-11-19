<?php

namespace Modules\Offer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferInformation;

/**
 * @extends Factory<OfferInformation>
 */
class OfferInformationFactory extends Factory
{
    protected $model = OfferInformation::class;

    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            'title' => ['fr' => 'Arrivée'],
            'description' => ['fr' => 'Les clés sont chez le gardien.'],
            'position' => 0,
        ];
    }
}
