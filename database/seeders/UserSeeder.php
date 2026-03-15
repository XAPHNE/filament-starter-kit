<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'super.admin@example.test'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('SuperSecret123!'),
                'force_password_reset' => false,
                'two_factor_type' => 'disabled',
            ]
        );

        if (! $user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }
    }
}
