<?php

namespace Modules\Offer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Offer\Enums\BookingStatusEnum;
use Modules\Offer\Enums\BookingTypeEnum;
use Modules\Offer\Models\Booking;
use Modules\Offer\Models\Offer;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $in = today()->addDays(10);

        return [
            'offer_id' => Offer::factory(),
            'user_id' => User::factory(),
            'type' => BookingTypeEnum::STAY,
            'check_in' => $in,
            'check_out' => $in->copy()->addDays(3),
            'guests' => 2,
            'message' => null,
            'unit_price' => '45000.00',
            'total' => '135000.00',
            'currency' => 'XAF',
            'status' => BookingStatusEnum::PENDING,
        ];
    }

    public function visit(): static
    {
        return $this->state(['type' => BookingTypeEnum::VISIT, 'check_out' => null, 'guests' => null, 'total' => null]);
    }

    public function status(BookingStatusEnum $status): static
    {
        return $this->state(['status' => $status]);
    }
}
