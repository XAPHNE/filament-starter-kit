<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PasswordHistory extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id']) // Don't log the hashed password itself in the audit log properties
            ->logOnlyDirty();
    }
    protected $fillable = [
        'user_id',
        'password',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
