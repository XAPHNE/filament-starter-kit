<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Auth\Pages\EditProfile;
use App\Models\User;
use Slimani\MediaManager\MediaManagerPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->registration(fn () => (bool) \App\Models\Setting::where('key', 'allow_registration')->first()?->value ? \App\Filament\Pages\Auth\Register::class : null)
            ->emailVerification()
            ->profile(\App\Filament\Pages\Auth\EditProfile::class)
            ->passwordReset(\App\Filament\Pages\Auth\PasswordReset\RequestPasswordReset::class)
            ->multiFactorAuthentication(
                providers: [
                    \App\Auth\MultiFactor\App\AppAuthentication::make(),
                    \App\Auth\MultiFactor\Email\EmailAuthentication::make(),
                ],
                isRequired: function ($user) {
                    if (! $user instanceof \App\Models\User) {
                        return false;
                    }

                    return $user->isMfaRequired();
                },
            )
            ->colors([
                'primary' => [
                    50 => Color::Zinc[50], 100 => Color::Zinc[100], 200 => Color::Zinc[200], 300 => Color::Zinc[300], 400 => Color::Zinc[400],
                    500 => Color::Zinc[700], 600 => Color::Zinc[800], 700 => Color::Zinc[900], 800 => Color::Zinc[950], 900 => Color::Zinc[950], 950 => Color::Zinc[950],
                ],
                'danger' => [
                    50 => Color::Red[50], 100 => Color::Red[100], 200 => Color::Red[200], 300 => Color::Red[300], 400 => Color::Red[400],
                    500 => Color::Red[700], 600 => Color::Red[800], 700 => Color::Red[900], 800 => Color::Red[950], 900 => Color::Red[950], 950 => Color::Red[950],
                ],
                'info' => [
                    50 => Color::Blue[50], 100 => Color::Blue[100], 200 => Color::Blue[200], 300 => Color::Blue[300], 400 => Color::Blue[400],
                    500 => Color::Blue[700], 600 => Color::Blue[800], 700 => Color::Blue[900], 800 => Color::Blue[950], 900 => Color::Blue[950], 950 => Color::Blue[950],
                ],
                'success' => [
                    50 => Color::Emerald[50], 100 => Color::Emerald[100], 200 => Color::Emerald[200], 300 => Color::Emerald[300], 400 => Color::Emerald[400],
                    500 => Color::Emerald[700], 600 => Color::Emerald[800], 700 => Color::Emerald[900], 800 => Color::Emerald[950], 900 => Color::Emerald[950], 950 => Color::Emerald[950],
                ],
                'warning' => [
                    50 => Color::Amber[50], 100 => Color::Amber[100], 200 => Color::Amber[200], 300 => Color::Amber[300], 400 => Color::Amber[400],
                    500 => Color::Amber[700], 600 => Color::Amber[800], 700 => Color::Amber[900], 800 => Color::Amber[950], 900 => Color::Amber[950], 950 => Color::Amber[950],
                ],
                'gray' => Color::Zinc,
            ])
            ->font('Inter')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                \App\Http\Middleware\EnforceConcurrentSessions::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\PasswordExpiryMiddleware::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogPlugin::make(),
                MediaManagerPlugin::make(),
            ]);
    }
}
