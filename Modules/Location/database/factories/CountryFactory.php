<?php

namespace Modules\Location\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Location\Models\Country;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->country();

        return [
            // QM à QZ : codes réservés à l'usage privé par l'ISO 3166, aucun pays du seeder.
            'code' => fake()->unique()->regexify('Q[M-Z]'),
            'name' => ['fr' => $name, 'en' => $name],
            'native_name' => $name,
            'dial_code' => '+'.fake()->numberBetween(1, 999),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
