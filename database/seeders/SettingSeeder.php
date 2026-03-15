<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'force_2fa' => '0',
            'allow_registration' => '0',
            'allow_account_deletion' => '0',
            'allow_email_updates' => '1',
            'default_roles' => '[]',
            'password_history_limit' => '0',
            'password_expiry_days' => '0',
            'min_password_length' => '8',
            'max_password_length' => '32',
            'require_uppercase' => '0',
            'require_lowercase' => '0',
            'require_number' => '0',
            'require_special_characters' => '0',
            'special_characters_list' => '!@#$%^&*()-_=+[]{}|;:,.<>?',
            'max_2fa_resend_attempts' => '3',
            'mfa_resend_lockdown_hours' => '1',
            'login_throttling_strategy' => 'hybrid',
            'max_login_attempts' => '5',
            'login_lockout_hours' => '1',
            'password_reset_limit' => '3',
            'password_reset_lockout_hours' => '24',
            'session_timeout' => '120',
            'logout_on_browser_close' => '0',
            'enable_concurrent_sessions' => '0',
            'default_concurrent_sessions' => '1',
            'enable_tier_based_concurrency' => '0',
            'default_tier' => null,
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
