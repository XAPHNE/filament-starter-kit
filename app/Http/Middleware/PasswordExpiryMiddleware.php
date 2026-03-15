<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PasswordExpiryMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user instanceof \App\Models\User) {
            $expiryDays = (int) \App\Models\Setting::where('key', 'password_expiry_days')->first()?->value ?? 0;

            if ($expiryDays > 0) {
                $passwordChangedAt = $user->password_changed_at ?? $user->created_at ?? now();

                if ($passwordChangedAt->copy()->addDays($expiryDays)->isPast()) {
                    // Only redirect if not already on the profile page or logging out
                    if (! $request->routeIs('filament.admin.auth.profile') && 
                        ! $request->routeIs('filament.admin.auth.logout')) {
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Password Expired')
                            ->body("Your password has expired. Please update it to continue using the system.")
                            ->warning()
                            ->persistent()
                            ->send();

                        return redirect()->to(filament()->getProfileUrl() . '?tab=security');
                    }
                }
            }

            if ($user->force_password_reset) {
                if (! $request->routeIs('filament.admin.auth.profile') && 
                    ! $request->routeIs('filament.admin.auth.logout')) {
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Password Reset Required')
                        ->body("You must reset your password to continue using the system.")
                        ->warning()
                        ->persistent()
                        ->send();

                    return redirect()->to(filament()->getProfileUrl() . '?tab=security');
                }
            }
        }

        return $next($request);
    }
}
