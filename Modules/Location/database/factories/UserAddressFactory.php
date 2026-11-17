<?php

namespace Modules\Location\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Enums\GenderEnum;
use Modules\Auth\Models\User;
use Modules\Location\Models\Address;
use Modules\Location\Models\UserAddress;

/**
 * @extends Factory<UserAddress>
 */
class UserAddressFactory extends Factory
{
    protected $model = UserAddress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'address_id' => Address::factory(),
            'gender' => fake()->randomElement(GenderEnum::cases()),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
        ];
    }
}
