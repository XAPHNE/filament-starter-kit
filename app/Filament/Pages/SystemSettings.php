<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use BezhanSalleh\FilamentShield\Support\Utils;
use UnitEnum;

class SystemSettings extends Page
{
    protected static UnitEnum | string | null $navigationGroup = 'Settings';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->getSchema('form')->fill([
            'force_2fa' => (bool) Setting::where('key', 'force_2fa')->first()?->value,
            'allow_registration' => (bool) Setting::where('key', 'allow_registration')->first()?->value,
            'allow_account_deletion' => (bool) Setting::where('key', 'allow_account_deletion')->first()?->value,
            'allow_email_updates' => (bool) Setting::where('key', 'allow_email_updates')->first()?->value,
            'default_roles' => json_decode(Setting::where('key', 'default_roles')->first()?->value ?? '[]', true),
            'password_history_limit' => (int) Setting::where('key', 'password_history_limit')->first()?->value ?? 0,
            'password_expiry_days' => (int) Setting::where('key', 'password_expiry_days')->first()?->value ?? 0,
            'min_password_length' => (int) Setting::where('key', 'min_password_length')->first()?->value ?? 8,
            'max_password_length' => (int) Setting::where('key', 'max_password_length')->first()?->value ?? 32,
            'require_uppercase' => (bool) Setting::where('key', 'require_uppercase')->first()?->value,
            'require_lowercase' => (bool) Setting::where('key', 'require_lowercase')->first()?->value,
            'require_number' => (bool) Setting::where('key', 'require_number')->first()?->value,
            'require_special_characters' => (bool) Setting::where('key', 'require_special_characters')->first()?->value,
            'special_characters_list' => Setting::where('key', 'special_characters_list')->first()?->value ?? '!@#$%^&*()-_=+[]{}|;:,.<>?',
            'max_2fa_resend_attempts' => (int) Setting::where('key', 'max_2fa_resend_attempts')->first()?->value ?? 3,
            'mfa_resend_lockdown_hours' => (int) Setting::where('key', 'mfa_resend_lockdown_hours')->first()?->value ?? 1,
            'login_throttling_strategy' => Setting::where('key', 'login_throttling_strategy')->first()?->value ?? 'hybrid',
            'max_login_attempts' => (int) Setting::where('key', 'max_login_attempts')->first()?->value ?? 5,
            'login_lockout_hours' => (int) Setting::where('key', 'login_lockout_hours')->first()?->value ?? 1,
            'password_reset_limit' => (int) Setting::where('key', 'password_reset_limit')->first()?->value ?? 3,
            'password_reset_lockout_hours' => (int) Setting::where('key', 'password_reset_lockout_hours')->first()?->value ?? 24,
            'session_timeout' => (int) Setting::where('key', 'session_timeout')->first()?->value ?? config('session.lifetime'),
            'logout_on_browser_close' => (bool) Setting::where('key', 'logout_on_browser_close')->first()?->value,
            'enable_concurrent_sessions' => (bool) Setting::where('key', 'enable_concurrent_sessions')->first()?->value,
            'default_concurrent_sessions' => (int) Setting::where('key', 'default_concurrent_sessions')->first()?->value ?? 1,
            'enable_tier_based_concurrency' => (bool) Setting::where('key', 'enable_tier_based_concurrency')->first()?->value,
            'default_tier' => Setting::where('key', 'default_tier')->first()?->value,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('User Registration')
                            ->icon(Heroicon::OutlinedUsers)
                            ->schema([
                                Section::make('Registration & Account Lifecycle')
                                    ->schema([
                                        Toggle::make('allow_registration')
                                            ->label('Enable Public Registration')
                                            ->helperText('If enabled, users can register for an account from the login page.')
                                            ->live(),
                                        Toggle::make('allow_account_deletion')
                                            ->label('Allow Users to Delete Accounts')
                                            ->helperText('If enabled, users can delete their own accounts from their profile page.')
                                            ->live(),
                                        Toggle::make('allow_email_updates')
                                            ->label('Allow Users to Update Email')
                                            ->helperText('If enabled, users can change their email address from their profile page.')
                                            ->live(),
                                        Select::make('default_roles')
                                            ->label('Default Assigned Role(s)')
                                            ->helperText('Select the roles that will be automatically assigned to newly registered users.')
                                            ->options(Utils::getRoleModel()::pluck('name', 'name')->toArray())
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn (Get $get) => (bool) $get('allow_registration')),
                                        Select::make('default_tier')
                                            ->label('Default Assigned Tier')
                                            ->helperText('The tier automatically assigned to newly registered users.')
                                            ->options(\App\Models\Tier::pluck('name', 'id')->toArray())
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn (Get $get) => (bool) $get('allow_registration') && (bool) $get('enable_concurrent_sessions') && (bool) $get('enable_tier_based_concurrency')),
                                    ]),
                            ]),
                        Tabs\Tab::make('Password Policy')
                            ->icon(Heroicon::OutlinedKey)
                            ->schema([
                                Section::make('History & Expiry')
                                    ->schema([
                                        TextInput::make('password_history_limit')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(20)
                                            ->label('Password History Limit')
                                            ->helperText('Prevent users from reusing their last X passwords. Set to 0 to disable.'),
                                        TextInput::make('password_expiry_days')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(365)
                                            ->label('Password Expiry (Days)')
                                            ->helperText('Require users to change their password every X days. Set to 0 to disable.'),
                                    ])->columns(2),
                                Section::make('Complexity Requirements')
                                    ->schema([
                                        TextInput::make('min_password_length')
                                            ->numeric()
                                            ->minValue(4)
                                            ->maxValue(128)
                                            ->label('Minimum Length')
                                            ->default(8),
                                        TextInput::make('max_password_length')
                                            ->numeric()
                                            ->minValue(4)
                                            ->maxValue(128)
                                            ->label('Maximum Length')
                                            ->default(32),
                                        Toggle::make('require_uppercase')
                                            ->label('Require Uppercase'),
                                        Toggle::make('require_lowercase')
                                            ->label('Require Lowercase'),
                                        Toggle::make('require_number')
                                            ->label('Require Numbers'),
                                        Toggle::make('require_special_characters')
                                            ->label('Require Special Chars')
                                            ->live(),
                                        TextInput::make('special_characters_list')
                                            ->label('Allowed Special Characters')
                                            ->visible(fn (Get $get) => (bool) $get('require_special_characters'))
                                            ->placeholder('!@#$%^&*()...'),
                                    ])->columns(2),
                            ]),
                        Tabs\Tab::make('Security')
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->schema([
                                Section::make('Multi-Factor Authentication')
                                    ->schema([
                                        Toggle::make('force_2fa')
                                            ->label('Require Multi-Factor Authentication (MFA)')
                                            ->helperText('If enabled, all users will be required to set up MFA. Email authentication will be the default.')
                                            ->live(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Throttling & Lockout')
                            ->icon(Heroicon::OutlinedClock)
                            ->schema([
                                Section::make('2FA Resend Limits')
                                    ->schema([
                                        TextInput::make('max_2fa_resend_attempts')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(10)
                                            ->label('Max Resend Attempts'),
                                        TextInput::make('mfa_resend_lockdown_hours')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(72)
                                            ->label('Lockdown Duration (Hours)')
                                            ->helperText('Duration of lockout after exceeding resend attempts.'),
                                    ])->columns(2),
                                Section::make('Login Throttling')
                                    ->schema([
                                        Select::make('login_throttling_strategy')
                                            ->label('Throttling Strategy')
                                            ->options([
                                                'account' => 'Account Based Only',
                                                'hybrid' => 'Hybrid (IP + Account Based)',
                                            ])
                                            ->required(),
                                        TextInput::make('max_login_attempts')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(20)
                                            ->label('Maximum Login Attempts'),
                                        TextInput::make('login_lockout_hours')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(72)
                                            ->label('Lockout Duration (Hours)'),
                                    ])->columns(3),
                                Section::make('Password Reset Limits')
                                    ->schema([
                                        TextInput::make('password_reset_limit')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(20)
                                            ->label('Max Reset Attempts'),
                                        TextInput::make('password_reset_lockout_hours')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(72)
                                            ->label('Lockout Duration (Hours)'),
                                    ])->columns(2),
                            ]),
                        Tabs\Tab::make('Sessions')
                            ->icon(Heroicon::OutlinedComputerDesktop)
                            ->schema([
                                Section::make('Session Management')
                                    ->schema([
                                        TextInput::make('session_timeout')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(1440)
                                            ->label('Session Timeout (minutes)')
                                            ->helperText('The number of minutes of inactivity before a session expires.')
                                            ->default(120),
                                        Toggle::make('logout_on_browser_close')
                                            ->label('Logout on Browser Close')
                                            ->helperText('If enabled, sessions will expire when the browser is closed.'),
                                    ])->columns(2),
                                Section::make('Concurrent Sessions')
                                    ->schema([
                                        Toggle::make('enable_concurrent_sessions')
                                            ->label('Enable Concurrent Sessions')
                                            ->helperText('Allow users to have multiple active sessions at the same time.')
                                            ->live(),
                                        TextInput::make('default_concurrent_sessions')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->label('Default Concurrent Sessions')
                                            ->visible(fn (Get $get) => (bool) $get('enable_concurrent_sessions'))
                                            ->default(1),
                                        Toggle::make('enable_tier_based_concurrency')
                                            ->label('Enable Tier-Based Concurrency')
                                            ->helperText('Apply concurrency limits based on user tiers.')
                                            ->visible(fn (Get $get) => (bool) $get('enable_concurrent_sessions'))
                                            ->live(),
                                        Actions::make([
                                            Action::make('manage_tiers')
                                                ->label('Manage Tiers')
                                                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                                ->color('gray')
                                                ->url(fn () => \App\Filament\Resources\Tiers\TierResource::getUrl('index'))
                                                ->openUrlInNewTab(),
                                        ])
                                        ->visible(fn (Get $get) => (bool) $get('enable_concurrent_sessions') && (bool) $get('enable_tier_based_concurrency')),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->color('primary')
                ->action('save'),
        ];
    }

    public function save(): mixed
    {
        $data = $this->getSchema('form')->getState();

        Setting::updateOrCreate(['key' => 'force_2fa'], ['value' => $data['force_2fa'] ?? false]);
        Setting::updateOrCreate(['key' => 'allow_registration'], ['value' => $data['allow_registration'] ?? false]);
        Setting::updateOrCreate(['key' => 'allow_account_deletion'], ['value' => $data['allow_account_deletion'] ?? false]);
        Setting::updateOrCreate(['key' => 'allow_email_updates'], ['value' => $data['allow_email_updates'] ?? false]);
        Setting::updateOrCreate(['key' => 'default_roles'], ['value' => json_encode($data['default_roles'] ?? [])]);
        
        Setting::updateOrCreate(['key' => 'password_history_limit'], ['value' => $data['password_history_limit'] ?? 0]);
        Setting::updateOrCreate(['key' => 'password_expiry_days'], ['value' => $data['password_expiry_days'] ?? 0]);
        Setting::updateOrCreate(['key' => 'min_password_length'], ['value' => $data['min_password_length'] ?? 8]);
        Setting::updateOrCreate(['key' => 'max_password_length'], ['value' => $data['max_password_length'] ?? 32]);
        Setting::updateOrCreate(['key' => 'require_uppercase'], ['value' => $data['require_uppercase'] ?? false]);
        Setting::updateOrCreate(['key' => 'require_lowercase'], ['value' => $data['require_lowercase'] ?? false]);
        Setting::updateOrCreate(['key' => 'require_number'], ['value' => $data['require_number'] ?? false]);
        Setting::updateOrCreate(['key' => 'require_special_characters'], ['value' => $data['require_special_characters'] ?? false]);
        Setting::updateOrCreate(['key' => 'special_characters_list'], ['value' => $data['special_characters_list'] ?? null]);

        Setting::updateOrCreate(['key' => 'max_2fa_resend_attempts'], ['value' => $data['max_2fa_resend_attempts'] ?? 3]);
        Setting::updateOrCreate(['key' => 'mfa_resend_lockdown_hours'], ['value' => $data['mfa_resend_lockdown_hours'] ?? 1]);
        Setting::updateOrCreate(['key' => 'login_throttling_strategy'], ['value' => $data['login_throttling_strategy'] ?? 'hybrid']);
        Setting::updateOrCreate(['key' => 'max_login_attempts'], ['value' => $data['max_login_attempts'] ?? 5]);
        Setting::updateOrCreate(['key' => 'login_lockout_hours'], ['value' => $data['login_lockout_hours'] ?? 1]);
        Setting::updateOrCreate(['key' => 'password_reset_limit'], ['value' => $data['password_reset_limit'] ?? 3]);
        Setting::updateOrCreate(['key' => 'password_reset_lockout_hours'], ['value' => $data['password_reset_lockout_hours'] ?? 24]);

        Setting::updateOrCreate(['key' => 'session_timeout'], ['value' => $data['session_timeout'] ?? 120]);
        Setting::updateOrCreate(['key' => 'logout_on_browser_close'], ['value' => $data['logout_on_browser_close'] ?? false]);
        Setting::updateOrCreate(['key' => 'enable_concurrent_sessions'], ['value' => $data['enable_concurrent_sessions'] ?? false]);
        Setting::updateOrCreate(['key' => 'default_concurrent_sessions'], ['value' => $data['default_concurrent_sessions'] ?? 1]);
        Setting::updateOrCreate(['key' => 'enable_tier_based_concurrency'], ['value' => $data['enable_tier_based_concurrency'] ?? false]);
        Setting::updateOrCreate(['key' => 'default_tier'], ['value' => $data['default_tier'] ?? null]);

        Notification::make()
            ->title('Settings saved successfully.')
            ->success()
            ->send();

        return redirect(route('filament.admin.pages.system-settings'));
    }
}
