<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\SupplySchedule;
use App\Models\SupplyScheduleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplyScheduleItem>
 */
class SupplyScheduleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supply_schedule_id' => SupplySchedule::factory(),
            'customer_id' => Customer::factory(),
            'qty' => fake()->numberBetween(10, 200),
        ];
    }
}
