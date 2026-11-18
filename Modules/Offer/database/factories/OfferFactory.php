<?php

namespace Modules\Offer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Location\Models\Country;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Models\Offer;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Le pays actif déjà présent (l'espace pays des tests), sinon un nouveau.
            'country_id' => fn (): int => (int) (Country::query()->where('active', true)->value('id') ?? Country::factory()->create()->getKey()),
            'title' => 'Offre '.fake()->unique()->numberBetween(1, 1000000),
            'description' => null,
            'status' => OfferStatusEnum::DRAFT,
            'price' => null,
            'currency' => null,
            'details' => [],
        ];
    }
}
