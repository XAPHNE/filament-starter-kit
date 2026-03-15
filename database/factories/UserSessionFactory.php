<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSession>
 */
class UserSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'last_activity' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
