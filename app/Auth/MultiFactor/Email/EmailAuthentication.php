<?php

namespace App\Auth\MultiFactor\Email;

use Filament\Auth\MultiFactor\Email\EmailAuthentication as BaseEmailAuthentication;
use App\Models\Setting;
use Illuminate\Support\Facades\RateLimiter;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Illuminate\Database\Eloquent\Model;
use Filament\Auth\MultiFactor\Email\Actions\DisableEmailAuthenticationAction;
use Filament\Auth\MultiFactor\Email\Actions\SetUpEmailAuthenticationAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Text;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Actions\Action;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;

class EmailAuthentication extends BaseEmailAuthentication
{
    public function isEnabled(\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        /** @var \App\Models\User $user */
        return $user->hasEmailAuthentication();
    }

    public function getManagementSchemaComponents(): array
    {
        $user = Filament::auth()->user();
        $isEnabled = $this->isEnabled($user);
        $isForced = (bool) Setting::where('key', 'force_2fa')->first()?->value;

        return [
            Actions::make($this->getActions())
                ->label('Email OTP')
                ->belowContent(function () use ($user, $isForced) {
                    if ($isForced && $user->two_factor_type === null) {
                        return 'MFA is ENFORCED by security policy. You must enable a method to continue. This will send a code to ' . $user->email . '.';
                    }
                    return 'Receive a 6-digit code via email (' . $user->email . ') during login.';
                })
                ->afterLabel(fn () => $isEnabled
                    ? Text::make('Enabled')->badge()->color('success')
                    : Text::make('Not Configured')->badge()->color('gray')
                ),
        ];
    }

    public function getActions(): array
    {
        $user = Filament::auth()->user();
        $isForced = (bool) Setting::where('key', 'force_2fa')->first()?->value;
        $hasApp = (bool) filled($user->app_authentication_secret);

        return [
            SetUpEmailAuthenticationAction::make($this)
                ->hidden(fn (): bool => $this->isEnabled($user)),
            
            DisableEmailAuthenticationAction::make($this)
                ->visible(fn (): bool => $this->isEnabled($user))
                ->before(function (\Filament\Actions\Action $action) use ($isForced, $hasApp) {
                    if ($isForced && !$hasApp) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'email_otp' => 'Multi-Factor Authentication is enforced. You must set up an Authenticator App before disabling Email OTP.',
                        ]);
                    }
                }),
        ];
    }

    public function sendCode(HasEmailAuthentication $user): bool
    {
        if (! ($user instanceof Model)) {
            throw new \LogicException('The [' . $user::class . '] class must be an instance of [' . Model::class . '] to use email authentication.');
        }

        $maxAttempts = (int) Setting::where('key', 'max_2fa_resend_attempts')->first()?->value ?? 3;
        $lockoutHours = (int) Setting::where('key', 'mfa_resend_lockdown_hours')->first()?->value ?? 1;
        $decaySeconds = $lockoutHours * 3600;

        $rateLimitingKey = "filament-email-authentication:{$user->getKey()}";

        if (RateLimiter::tooManyAttempts($rateLimitingKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitingKey);
            $minutes = ceil($seconds / 60);
            abort(429, "Too many resend attempts. Please try again in {$minutes} minutes.");
        }

        RateLimiter::hit($rateLimitingKey, $decaySeconds);

        $code = $this->generateCode();
        $codeExpiryMinutes = $this->getCodeExpiryMinutes();

        session()->put('filament_email_authentication_code', \Illuminate\Support\Facades\Hash::make($code));
        session()->put('filament_email_authentication_code_expires_at', now()->addMinutes($codeExpiryMinutes));

        $user->notify(app($this->getCodeNotification(), [
            'code' => $code,
            'codeExpiryMinutes' => $codeExpiryMinutes,
        ]));

        return true;
    }

    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            OneTimeCodeInput::make('code')
                ->label('Verification Code')
                ->validationAttribute('code')
                ->belowContent(
                    \Filament\Schemas\Components\Actions::make([
                        Action::make('resend')
                            ->label('Resend Code')
                            ->link()
                            ->color('gray')
                            ->action(function () use ($user): void {
                                if (! $this->sendCode($user)) {
                                    Notification::make()
                                        ->title('Too many attempts. Please try again later.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                Notification::make()
                                    ->title('A new code has been sent to your email.')
                                    ->success()
                                    ->send();
                            })
                    ])->alignment(\Filament\Support\Enums\Alignment::Center)
                )
                ->required()
                ->rule(function (): Closure {
                    return function (string $attribute, $value, Closure $fail): void {
                        if ($this->verifyCode($value)) {
                            return;
                        }

                        $fail('The code you entered is invalid or has expired.');
                    };
                }),
        ];
    }
}
