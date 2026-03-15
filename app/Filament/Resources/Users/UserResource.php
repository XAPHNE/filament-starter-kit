<?php

namespace App\Filament\Resources\Users;

use App\Filament\Exports\UserExporter;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\RateLimiter;
use Filament\Actions\Action;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static UnitEnum | string | null $navigationGroup = 'Filament Shield';
    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-users';

    protected static ?string $recordTitleAttribute = 'Users';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Tabs::make('User Management')
                    ->tabs([
                        \Filament\Schemas\Components\Tabs\Tab::make('Profile')
                            ->icon(Heroicon::User)
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Full Name')
                                            ->placeholder('John Doe')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->placeholder('john@example.com')
                                            ->required()
                                            ->unique(ignoreRecord: true),
                                    ]),
                                
                                \Filament\Schemas\Components\Grid::make(1)
                                    ->schema([
                                        Select::make('two_factor_type')
                                            ->label('MFA Enforcement')
                                            ->placeholder('Global (Follow System Settings)')
                                            ->options([
                                                'disabled' => 'Disabled (Exempt)',
                                                'email' => 'Email OTP',
                                                'app' => 'Authenticator App',
                                            ])
                                            ->default(null)
                                            ->selectablePlaceholder(true)
                                            ->helperText(fn ($record) => $record?->two_factor_type === 'app' && !filled($record->app_authentication_secret) ? 'Warning: Authenticator App is selected but no secret is set.' : 'Choose how this user should handle multi-factor authentication.'),
                                    ]),

                                \Filament\Schemas\Components\Section::make('Verification Status')
                                    ->compact()
                                    ->schema([
                                        Toggle::make('force_email_verification')
                                            ->label('Force Email Verification')
                                            ->helperText('Enable this to require the user to verify their email address before they can access the panel.')
                                            ->onColor('warning')
                                            ->afterStateHydrated(fn (Toggle $component, $record) => $component->state(blank($record?->email_verified_at)))
                                            ->dehydrated(false)
                                            ->saveRelationshipsUsing(function ($record, $state) {
                                                if ($state) {
                                                    $record->email_verified_at = null;
                                                } elseif (blank($record->email_verified_at)) {
                                                    $record->email_verified_at = now();
                                                }
                                            }),
                                    ]),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Security')
                            ->icon(Heroicon::ShieldCheck)
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Password Management')
                                    ->description('Leave password blank to keep current.')
                                    ->schema([
                                        \Filament\Schemas\Components\Grid::make(1)
                                            ->schema([
                                                TextInput::make('password')
                                                    ->password()
                                                    ->revealable()
                                                    ->dehydrated(fn (?string $state) => filled($state))
                                                    ->required(fn (string $operation): bool => $operation === 'create')
                                                    ->rule(\App\Models\Setting::getPasswordRules())
                                                    ->rule(fn ($record) => new \App\Rules\PasswordHistoryRule($record))
                                                    ->maxLength((int) \App\Models\Setting::get('max_password_length', 32))
                                                    ->confirmed(),
                                                TextInput::make('password_confirmation')
                                                    ->label('Confirm Password')
                                                    ->password()
                                                    ->revealable()
                                                    ->required(fn (string $operation): bool => $operation === 'create')
                                                    ->dehydrated(false),
                                            ]),
                                    ]),

                                \Filament\Schemas\Components\Section::make('Access Rules')
                                    ->compact()
                                    ->schema([
                                        Toggle::make('force_password_reset')
                                            ->label('Force Password Reset')
                                            ->helperText('The user will be prompted to change their password on next login.')
                                            ->default(false),
                                    ]),

                                \Filament\Schemas\Components\Section::make('Administrative Actions')
                                    ->description('Manage security locks and cooldowns for this user.')
                                    ->compact()
                                    ->visible(fn ($record) => $record !== null)
                                    ->schema([
                                        \Filament\Schemas\Components\Actions::make([
                                            Action::make('resetThrottlingForm')
                                                ->label('Reset Security Locks')
                                                ->modalHeading('Reset Security Locks')
                                                ->modalDescription('This will clear any temporary login, MFA, or password reset locks for this user across all their known IP addresses.')
                                                ->icon('heroicon-o-lock-open')
                                                ->color('warning')
                                                ->requiresConfirmation()
                                                ->action(function (User $record) {
                                                    static::resetUserThrottling($record);
                                                }),
                                        ]),
                                    ]),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Access & Tiers')
                            ->icon(Heroicon::Key)
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(1)
                                    ->schema([
                                        Select::make('roles')
                                            ->relationship('roles', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable(),
                                        Select::make('tiers')
                                            ->label('Assigned Tiers')
                                            ->relationship('tiers', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->helperText('Select tiers to apply concurrency limits or other tier-based features.'),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Users')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('two_factor_type')
                    ->label('MFA Type')
                    ->badge()
                    ->default('global')
                    ->color(fn (string $state): string => match ($state) {
                        'disabled' => 'danger',
                        'email' => 'info',
                        'app' => 'success',
                        'global' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'disabled' => 'Disabled (Exempt)',
                        'email' => 'Email OTP',
                        'app' => 'Authenticator App',
                        'global' => 'Global Inherited',
                        default => 'Unknown',
                    })
                    ->sortable(),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => filled($record->email_verified_at)),
                IconColumn::make('force_password_reset')
                    ->label('Password Reset')
                    ->boolean(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->placeholder('N/A'),
                TextColumn::make('tiers.name')
                    ->badge()
                    ->color('success')
                    ->separator(',')
                    ->placeholder('N/A'),
                TextColumn::make('creator.name')
                    ->placeholder('N/A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updater.name')
                    ->placeholder('N/A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleter.name')
                    ->placeholder('N/A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\IconColumn::make('app_authentication_secret')
                    ->label('App Data Set')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => filled($record->app_authentication_secret))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('two_factor_type')
                    ->label('MFA Type')
                    ->options([
                        'disabled' => 'Exempt (Disabled)',
                        'email' => 'Email',
                        'app' => 'App',
                        'null' => 'Global Inherited',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (blank($data['value'])) {
                            return $query;
                        }
                        if ($data['value'] === 'null') {
                            return $query->whereNull('two_factor_type');
                        }
                        return $query->where('two_factor_type', $data['value']);
                    }),
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                SelectFilter::make('force_password_reset')
                    ->options([
                        '1' => 'Yes',
                        '0' => 'No',
                    ])
                    ->default(null),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('resetThrottling')
                    ->label('Reset Locks')
                    ->modalHeading('Reset Security Locks')
                    ->modalDescription('This will clear any temporary login, MFA, or password reset locks for this user across all their known IP addresses.')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        static::resetUserThrottling($record);

                        \Filament\Notifications\Notification::make()
                            ->title('User security locks have been cleared.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make()->exporter(UserExporter::class),
                ]),
            ]);
    }

    public static function resetUserThrottling(User $record): void
    {
        // 1. Clear Email MFA Throttle (ID based)
        RateLimiter::clear("filament-email-authentication:{$record->getKey()}");

        // 2. Clear Email-only throttles
        RateLimiter::clear('login-throttle:' . $record->email);
        RateLimiter::clear('password-reset-throttle:' . $record->email);

        // 3. Clear IP-based hybrid throttles from known authentication logs
        if (method_exists($record, 'authentications')) {
            $ips = $record->authentications()->distinct()->pluck('ip_address');
            foreach ($ips as $ip) {
                RateLimiter::clear("login-throttle:{$ip}|{$record->email}");
                RateLimiter::clear("password-reset-throttle:{$ip}|{$record->email}");
            }
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
