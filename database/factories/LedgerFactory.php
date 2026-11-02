<?php

namespace Database\Factories;

use App\Enums\LedgerType;
use App\Models\Customer;
use App\Models\Ledger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ledger>
 */
class LedgerFactory extends Factory
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
            'type' => LedgerType::In,
            'qty' => fake()->numberBetween(10, 500),
            'date' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
        ];
    }
}
