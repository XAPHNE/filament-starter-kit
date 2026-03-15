<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use App\Models\Setting;
use Illuminate\Support\Facades\RateLimiter;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\SessionGuard;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $email = $data['email'];
        
        $strategy = Setting::where('key', 'login_throttling_strategy')->first()?->value ?? 'hybrid';
        $maxAttempts = (int) Setting::where('key', 'max_login_attempts')->first()?->value ?? 5;
        $lockoutHours = (int) Setting::where('key', 'login_lockout_hours')->first()?->value ?? 1;
        $decaySeconds = $lockoutHours * 3600;

        $key = 'login-throttle:' . ($strategy === 'hybrid' ? request()->ip() . '|' : '') . $email;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            abort(429, "Too many login attempts. Please try again in {$minutes} minutes.");
        }

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();

        $authProvider = $authGuard->getProvider();
        $credentials = $this->getCredentialsFromFormData($data);

        $user = $authProvider->retrieveByCredentials($credentials);

        if ((! $user) || (! $authProvider->validateCredentials($user, $credentials))) {
            RateLimiter::hit($key, $decaySeconds);
            
            $this->userUndertakingMultiFactorAuthentication = null;
            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        // Logic for MFA challenge if needed (inherited from parent would be better but we need to hit our limiter)
        // Since we hit the limiter on failure above, we are good for now. 
        // But we should probably use the parent's MFA logic if it exists.
        
        // Let's call the parent's authenticate but skip its rate limiting.
        // This is tricky because parent's authenticate has its own rateLimit(5) call.
        
        // Actually, let's just implement the full logic here to be safe and precise with our limiter.
        
        // MFA Logic (Manual check to match Panel bypass)
        /** @var \App\Models\User $user */
        $isMfaRequired = $user->isMfaRequired();

        if (
            filled($this->userUndertakingMultiFactorAuthentication) &&
            (decrypt($this->userUndertakingMultiFactorAuthentication) === $user->getAuthIdentifier())
        ) {
            if ($this->isMultiFactorChallengeRateLimited($user)) {
                return null;
            }

            $this->multiFactorChallengeForm->validate();
        } else {
            foreach (Filament::getMultiFactorAuthenticationProviders() as $multiFactorAuthenticationProvider) {
                if (! $multiFactorAuthenticationProvider->isEnabled($user)) {
                    continue;
                }

                $this->userUndertakingMultiFactorAuthentication = encrypt($user->getAuthIdentifier());

                if ($multiFactorAuthenticationProvider instanceof \Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook) {
                    $multiFactorAuthenticationProvider->beforeChallenge($user);
                }

                break;
            }

            if (filled($this->userUndertakingMultiFactorAuthentication)) {
                $this->multiFactorChallengeForm->fill();

                return null;
            }
        }

        if (! $authGuard->attemptWhen($credentials, function (Authenticatable $user): bool {
            if (! ($user instanceof FilamentUser)) {
                return true;
            }

            return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
        }, $data['remember'] ?? false)) {
            RateLimiter::hit($key, $decaySeconds);
            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        RateLimiter::clear($key);
        session()->regenerate();

        return app(LoginResponse::class);
    }
}
