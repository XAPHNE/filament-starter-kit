<?php

namespace Database\Factories;

use App\Models\Tier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tier>
 */
class TierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(),
            'concurrent_sessions' => $this->faker->numberBetween(1, 10),
        ];
    }
}
