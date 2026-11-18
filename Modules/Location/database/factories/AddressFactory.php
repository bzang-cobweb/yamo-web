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
            // Le pays actif déjà présent (l'espace pays des tests), sinon un nouveau.
            'country_id' => fn (): int => (int) (Country::query()->where('active', true)->value('id') ?? Country::factory()->create()->getKey()),
            'street' => fake()->unique()->streetAddress(),
            'zip' => fake()->postcode(),
            'city' => fake()->city(),
            'latitude' => null,
            'longitude' => null,
        ];
    }
}
