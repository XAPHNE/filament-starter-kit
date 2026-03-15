<?php

namespace App\Filament\Pages\Auth\PasswordReset;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use App\Models\Setting;
use Illuminate\Support\Facades\RateLimiter;
use Filament\Facades\Filament;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function request(): void
    {
        $data = $this->form->getState();
        $email = $data['email'];

        $maxAttempts = (int) Setting::where('key', 'password_reset_limit')->first()?->value ?? 3;
        $lockoutHours = (int) Setting::where('key', 'password_reset_lockout_hours')->first()?->value ?? 24;
        $decaySeconds = $lockoutHours * 3600;

        $key = 'password-reset-throttle:' . request()->ip() . '|' . $email;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $hours = ceil($seconds / 3600);
            abort(429, "Too many password reset requests. Please try again in {$hours} hours.");
        }

        RateLimiter::hit($key, $decaySeconds);

        parent::request();
    }
}
