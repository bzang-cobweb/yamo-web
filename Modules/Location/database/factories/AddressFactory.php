<?php

namespace Modules\Location\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Location\Models\Address;
use Modules\Location\Models\Country;
use Modules\Location\Models\Region;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            // Une région du pays actif déjà présent (l'espace pays des tests), sinon d'un nouveau : celle qui porte le
            // nom de la ville, créée au besoin (décision 2026-11-25-adresse-sans-pays).
            'region_id' => fn (array $attributes): int => self::regionFor(
                (int) (Country::query()->where('active', true)->value('id') ?? Country::factory()->create()->getKey()),
                (string) $attributes['city'],
            ),
            'street' => fake()->unique()->streetAddress(),
            'zip' => fake()->postcode(),
            'city' => fake()->city(),
            'latitude' => null,
            'longitude' => null,
        ];
    }

    /**
     * Dans ce pays : dans sa région qui porte le nom de la ville, créée au besoin. Remplace `for($country)`, l'adresse
     * n'ayant plus de pays à elle.
     */
    public function inCountry(Country|int $country): static
    {
        $id = $country instanceof Country ? (int) $country->getKey() : $country;

        return $this->state(['region_id' => fn (array $attributes): int => self::regionFor($id, (string) $attributes['city'])]);
    }

    /**
     * `for($country)` comme avant : dans ce pays (`inCountry()`).
     */
    public function for($factory, $relationship = null)
    {
        if ($factory instanceof Factory && $factory->modelName() === Country::class) {
            $factory = $factory->create();
        }
        if ($factory instanceof Country) {
            return $this->inCountry($factory);
        }

        return parent::for($factory, $relationship);
    }

    /** La région de ce pays qui porte le nom de la ville, créée au besoin : deux adresses d'une ville, une région. */
    private static function regionFor(int $country, string $city): int
    {
        // Sans casse ni espaces en trop, comme l'empreinte de l'adresse.
        $plain = fn (string $name): string => Str::lower(Str::squish($name));
        $region = Region::query()->where('country_id', $country)->get()
            ->first(fn (Region $region): bool => $plain((string) $region->getTranslation('name', 'fr')) === $plain($city))
            ?? Region::factory()->create(['country_id' => $country, 'name' => ['fr' => Str::squish($city), 'en' => Str::squish($city)]]);

        return (int) $region->getKey();
    }
}
