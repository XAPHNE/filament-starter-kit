<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SessionSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole() || $this->app->runningUnitTests()) {
            try {
                if (\Schema::hasTable('settings')) {
                    $settings = \App\Models\Setting::whereIn('key', [
                        'session_timeout',
                        'logout_on_browser_close',
                    ])->pluck('value', 'key');

                    if (isset($settings['session_timeout'])) {
                        config(['session.lifetime' => (int) $settings['session_timeout']]);
                    }

                    if (isset($settings['logout_on_browser_close'])) {
                        config(['session.expire_on_close' => (bool) $settings['logout_on_browser_close']]);
                    }
                }
            } catch (\Exception $e) {
                // Silently fail if DB is not ready
            }
        }
    }
}
