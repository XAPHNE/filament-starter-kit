<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Notifications\Notification;
use App\Models\Setting;
use App\Models\UserSession;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Hash;
use Filament\Schemas\Components\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Enums\Width;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Schemas\Components\Group;
use Illuminate\Support\Arr;

class EditProfile extends BaseEditProfile
{
    protected static string $layout = 'filament-panels::components.layout.index';

    public function getMaxWidth(): Width | string | null
    {
        return Width::Full;
    }

    public static function isSimple(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Profile')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon(Heroicon::OutlinedUser)
                            ->schema([
                                Section::make('Profile Information')
                                    ->description('Update your account\'s profile information and email address.')
                                    ->schema([
                                        $this->getNameFormComponent(),
                                        $this->getEmailFormComponent(),
                                        \Filament\Forms\Components\Placeholder::make('email_verification_status')
                                            ->label('Status')
                                            ->content(fn () => auth()->user()->hasVerifiedEmail() 
                                                ? new \Illuminate\Support\HtmlString('<span class="text-success-600 dark:text-success-400 flex items-center gap-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg> Verified</span>') 
                                                : new \Illuminate\Support\HtmlString('<span class="text-danger-600 dark:text-danger-400 flex items-center gap-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg> Unverified</span>')),
                                    ]),
                            ]),
                        Tabs\Tab::make('Security')
                            ->id('security')
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->schema([
                                Section::make('Update Password')
                                    ->description('Ensure your account is using a long, random password to stay secure.')
                                    ->schema([
                                        TextInput::make('current_password')
                                            ->label('Current Password')
                                            ->password()
                                            ->revealable()
                                            ->requiredWith('password')
                                            ->currentPassword()
                                            ->dehydrated(false),
                                        $this->getPasswordFormComponent(),
                                        $this->getPasswordConfirmationFormComponent(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Multi-Factor Auth')
                            ->icon(Heroicon::OutlinedLockClosed)
                            ->schema(function () {
                                $isForced = (bool) \App\Models\Setting::get('force_2fa', false);
                                
                                return [
                                    Section::make('Secure Your Account')
                                        ->description('Add additional security to your account using multi-factor authentication.')
                                        ->schema($this->getMfaProviderComponents()),
                                ];
                            }),
                        Tabs\Tab::make('Browser Sessions')
                            ->icon(Heroicon::OutlinedComputerDesktop)
                            ->schema([
                                Section::make('Active Sessions')
                                    ->description('Manage and log out your active sessions on other browsers and devices.')
                                    ->schema([
                                        ViewField::make('sessions')
                                            ->view('filament.pages.auth.edit-profile.sessions'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Danger Zone')
                            ->icon(Heroicon::OutlinedTrash)
                            ->schema([
                                Section::make('Delete Account')
                                    ->description('Permanently delete your account.')
                                    ->schema([
                                        Placeholder::make('delete_account_warning')
                                            ->content('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'),
                                        Actions::make([
                                            $this->getDeleteAccountAction()->submit(false)->button(),
                                        ]),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function logoutSession($sessionId): void
    {
        /** @var \App\Models\UserSession $session */
        $session = UserSession::find($sessionId);

        if ($session && $session->user_id === auth()->id()) {
            $sid = $session->session_id;
            
            // 1. Core sessions table
            if (config('session.driver') === 'database') {
                \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                    ->where('id', $sid)
                    ->delete();
            }

            // 2. Tracking table
            $session->delete();

            Notification::make()
                ->title('Session terminated.')
                ->success()
                ->send();
            
            $this->dispatch('refresh-sessions');
        }
    }

    public function logoutOtherBrowserSessions(): void
    {
        $currentSessionId = session()->getId();

        // 1. Core table
        if (config('session.driver') === 'database') {
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', auth()->id())
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        // 2. Tracking table
        UserSession::where('user_id', auth()->id())
            ->where('session_id', '!=', $currentSessionId)
            ->delete();

        Notification::make()
            ->title('Other browser sessions logged out.')
            ->success()
            ->send();
            
        $this->dispatch('refresh-sessions');
    }


    protected function getEmailFormComponent(): Component
    {
        $component = parent::getEmailFormComponent();

        if (! (bool) Setting::where('key', 'allow_email_updates')->first()?->value) {
            $component->disabled();
        }

        return $component;
    }

    protected function getPasswordFormComponent(): Component
    {
        $component = parent::getPasswordFormComponent();

        $component->rule(Setting::getPasswordRules());
        /** @var \App\Models\User $user */
        $user = $this->getUser();
        $component->rule(new \App\Rules\PasswordHistoryRule($user));
        $component->maxLength((int) Setting::where('key', 'max_password_length')->first()?->value ?? 32);

        return $component;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    public function getMfaProviderComponents(): array
    {
        $user = $this->getUser();

        return collect(filament()->getMultiFactorAuthenticationProviders())
            ->map(function (MultiFactorAuthenticationProvider $provider) use ($user): Component {
                return Group::make($provider->getManagementSchemaComponents())
                    ->statePath($provider->getId());
            })
            ->all();
    }

    public function getMultiFactorAuthenticationContentComponent(): ?Component
    {
        return null;
    }

    protected function getDeleteAccountAction(): Action
    {
        return Action::make('deleteAccount')
            ->label('Delete Account')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete Your Account')
            ->modalDescription('Are you sure you want to delete your account? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete my account')
            ->action(function () {
                /** @var \App\Models\User $user */
                $user = $this->getUser();

                \Filament\Facades\Filament::auth()->logout();

                $user->delete();

                Notification::make()
                    ->title('Account deleted successfully.')
                    ->success()
                    ->send();

                return redirect()->to(filament()->getLoginUrl());
            });
    }
}
