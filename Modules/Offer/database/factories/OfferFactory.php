<?php

namespace Modules\Offer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
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
            // Un type de location, obligatoire (décision 2026-11-20-contrat-type-de-location).
            'details' => ['rental_type' => 'long_term'],
        ];
    }

    /**
     * À une adresse, avec son indication (décision 2026-11-19-contrat-adresse-offre-table).
     * Une fabrique d'adresses donne une adresse nouvelle à chaque offre.
     *
     * @param  Address|Factory<Address>  $address
     */
    public function at(Address|Factory $address, ?string $indication = null): static
    {
        return $this->afterCreating(fn (Offer $offer) => $offer->offerAddress()->create([
            'address_id' => ($address instanceof Address ? $address : $address->create())->getKey(),
            'indication' => $indication,
        ]));
    }
}
