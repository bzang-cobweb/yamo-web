<?php

namespace Modules\Location\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'street' => fake()->unique()->streetAddress(),
            'zip' => fake()->postcode(),
            'city' => fake()->city(),
            'latitude' => null,
            'longitude' => null,
        ];
    }
}
