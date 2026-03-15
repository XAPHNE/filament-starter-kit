<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            \Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog::class,
            \App\Policies\AuthenticationLogPolicy::class
        );

        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\ActivityLog::class,
            \App\Policies\ActivityLogPolicy::class
        );

        \Illuminate\Support\Facades\Gate::define('viewPulse', function (\App\Models\User $user) {
            return $user->id === 1; // Or check permission
        });
    }
}
