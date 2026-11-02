<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supply>
 */
class SupplyFactory extends Factory
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
            'qty' => fake()->numberBetween(10, 200),
            'date' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
        ];
    }
}
