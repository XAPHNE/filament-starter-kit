<?php

namespace App\Auth\MultiFactor\App;

use Filament\Auth\MultiFactor\App\AppAuthentication as BaseAppAuthentication;
use Filament\Auth\MultiFactor\App\Actions\DisableAppAuthenticationAction;
use Filament\Auth\MultiFactor\App\Actions\SetUpAppAuthenticationAction;
use Filament\Auth\MultiFactor\App\Actions\RegenerateAppAuthenticationRecoveryCodesAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use App\Models\Setting;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Forms\Components\OneTimeCodeInput;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;

class AppAuthentication extends BaseAppAuthentication
{
    public function isEnabled(\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        /** @var \App\Models\User $user */
        return $user->two_factor_type === 'app' && parent::isEnabled($user);
    }

    /**
     * @return array<Component>
     */
    public function getManagementSchemaComponents(): array
    {
        $user = Filament::auth()->user();
        $isEnabled = $this->isEnabled($user);
        $isForced = (bool) Setting::where('key', 'force_2fa')->first()?->value;

        return [
            Actions::make($this->getActions())
                ->label('Authenticator App')
                ->belowContent(function () use ($user, $isForced) {
                    if ($isForced && $user->two_factor_type === null) {
                        return 'MFA is ENFORCED by security policy. You must enable a method to continue. Authenticator apps provide the strongest protection.';
                    }
                    return 'Use mobile apps like Google Authenticator or Authy to generate secure codes.';
                })
                ->afterLabel(fn () => $isEnabled
                    ? Text::make('Enabled')->badge()->color('success')
                    : Text::make('Not Configured')->badge()->color('gray')
                ),
        ];
    }

    public function getActions(): array
    {
        /** @var \App\Models\User $user */
        $user = Filament::auth()->user();
        $isForced = (bool) Setting::where('key', 'force_2fa')->first()?->value;
        $hasEmail = $user->hasEmailAuthentication();

        return [
            SetUpAppAuthenticationAction::make($this)
                ->hidden(fn (): bool => $this->isEnabled($user)),
            
            RegenerateAppAuthenticationRecoveryCodesAction::make($this)
                ->visible(fn (): bool => $this->isEnabled($user) && $this->isRecoverable() && $this->canRegenerateRecoveryCodes()),
            
            DisableAppAuthenticationAction::make($this)
                ->visible(fn (): bool => $this->isEnabled($user))
                ->before(function (\Filament\Actions\Action $action) use ($isForced, $hasEmail) {
                    if ($isForced && !$hasEmail) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'app_mfa' => 'Multi-Factor Authentication is enforced. You must enable Email OTP before disabling your app.',
                        ]);
                    }
                }),
        ];
    }

    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            OneTimeCodeInput::make('code')
                ->label('Verification Code')
                ->validationAttribute('code')
                ->extraAttributes(['class' => 'justify-between gap-x-3'])
                ->extraInputAttributes(['class' => '!text-center'])
                ->required()
                ->rule(function (): Closure {
                    return function (string $attribute, $value, Closure $fail): void {
                        if ($this->verifyCode($value)) {
                            return;
                        }

                        $fail('The code you entered is invalid.');
                    };
                }),
        ];
    }
}
