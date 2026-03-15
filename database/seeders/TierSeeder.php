<?php

namespace Database\Seeders;

use App\Models\Tier;
use Illuminate\Database\Seeder;

class TierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Free',
                'description' => 'Standard free tier',
                'concurrent_sessions' => 1,
            ],
            [
                'name' => 'Pro',
                'description' => 'Professional tier for power users',
                'concurrent_sessions' => 3,
            ],
            [
                'name' => 'Business',
                'description' => 'Business tier for teams',
                'concurrent_sessions' => 10,
            ],
        ];

        foreach ($tiers as $tier) {
            Tier::updateOrCreate(
                ['name' => $tier['name']],
                $tier
            );
        }
    }
}
