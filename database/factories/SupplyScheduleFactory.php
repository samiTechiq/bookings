<?php

namespace Database\Factories;

use App\Models\SupplySchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplySchedule>
 */
class SupplyScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'user_id' => User::factory(),
        ];
    }
}
