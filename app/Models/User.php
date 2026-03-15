<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class User extends Authenticatable implements HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles, LogsActivity, AuthenticationLoggable, \Illuminate\Auth\MustVerifyEmail;
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
    use InteractsWithEmailAuthentication;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'two_factor_type', 'force_password_reset'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (auth()->check()) {
                $user->created_by = auth()->id();
            }
        });

        static::updating(function (User $user) {
            if (auth()->check()) {
                $user->updated_by = auth()->id();
            }
        });

        static::deleting(function (User $user) {
            if (auth()->check()) {
                $user->deleted_by = auth()->id();
                $user->save(); // Save the deleted_by before soft deleting
            }
        });


        static::saving(function (User $user) {
            if ($user->isDirty('password')) {
                $user->password_changed_at = now();
                $user->force_password_reset = false;
            }
        });

        static::saved(function (User $user) {
            if ($user->isDirty('password')) {
                $user->passwordHistories()->create([
                    'password' => $user->password,
                ]);

                $limit = (int) \App\Models\Setting::get('password_history_limit', 0);
                if ($limit > 0) {
                    $user->passwordHistories()
                        ->whereNotIn('id', $user->passwordHistories()->latest()->take($limit)->pluck('id'))
                        ->delete();
                }

                // Security: Purge all other active sessions when password changes
                if ($user->wasChanged('password')) {
                    $sid = session()->getId();
                    
                    // 1. Purge from our tracking table (user_sessions)
                    \App\Models\UserSession::where('user_id', $user->id)
                        ->where('session_id', '!=', $sid)
                        ->delete();

                    // 2. Purge from Laravel CORE sessions table if database driver is in use
                    if (config('session.driver') === 'database') {
                        \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                            ->where('user_id', $user->id)
                            ->where('id', '!=', $sid)
                            ->delete();
                    }
                }
            }
        });
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        if ($condition) {
            $this->two_factor_type = 'email';
        } elseif ((bool) \App\Models\Setting::get('force_2fa', false)) {
            // If forced, we can only switch to app if app is set up, otherwise we can't disable.
            if ($this->two_factor_type === 'email') {
                if (!filled($this->getAppAuthenticationSecret())) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'email_code' => 'MFA is required. Please set up an Authenticator App before disabling Email OTP.',
                    ]);
                }
                $this->two_factor_type = 'app';
            }
        } else {
            $this->two_factor_type = 'disabled';
        }

        $this->save();
    }

    public function hasEmailAuthentication(): bool
    {
        if ($this->two_factor_type === 'email') {
            return true;
        }

        if ($this->two_factor_type !== null) {
            return false;
        }

        return (bool) \App\Models\Setting::get('force_2fa', false);
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        if (blank($secret)) {
            if ($this->two_factor_type === 'app' && (bool) \App\Models\Setting::get('force_2fa', false)) {
                 $this->two_factor_type = 'email'; // Fallback to email if forced
            } elseif ($this->two_factor_type === 'app') {
                 $this->two_factor_type = 'disabled';
            }
        } else {
            $this->two_factor_type = 'app';
        }

        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function isMfaExempt(): bool
    {
        return trim((string) $this->two_factor_type) === 'disabled';
    }

    public function isMfaRequired(): bool
    {
        if (! (bool) \App\Models\Setting::get('force_2fa', false)) {
            return false;
        }

        return ! $this->isMfaExempt();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'force_password_reset',
        'two_factor_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
            'force_password_reset' => 'boolean',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withTrashed();
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by')->withTrashed();
    }

    public function updatedUsers()
    {
        return $this->hasMany(User::class, 'updated_by')->withTrashed();
    }

    public function deletedUsers()
    {
        return $this->hasMany(User::class, 'deleted_by');
    }

    public function passwordHistories()
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function tiers()
    {
        return $this->belongsToMany(Tier::class, 'tier_user');
    }
}
