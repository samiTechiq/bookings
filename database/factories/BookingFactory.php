<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'qty' => fake()->numberBetween(10, 500),
            'amount' => fake()->randomFloat(2, 0, 100000),
            'date' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
        ];
    }
}
