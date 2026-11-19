<?php

namespace Modules\Offer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Location\Models\Address;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferAddress;

/**
 * @extends Factory<OfferAddress>
 */
class OfferAddressFactory extends Factory
{
    protected $model = OfferAddress::class;

    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            'address_id' => Address::factory(),
            'indication' => null,
        ];
    }
}
