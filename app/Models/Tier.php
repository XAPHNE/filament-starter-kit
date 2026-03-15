<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'concurrent_sessions',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'tier_user');
    }

    public function creator(): BelongsTo
    {
        $relation = $this->belongsTo(User::class, 'created_by');

        $relation->withTrashed();

        return $relation;
    }

    public function updater(): BelongsTo
    {
        $relation = $this->belongsTo(User::class, 'updated_by');

        $relation->withTrashed();

        return $relation;
    }

    public function deleter(): BelongsTo
    {
        $relation = $this->belongsTo(User::class, 'deleted_by');

        $relation->withTrashed();

        return $relation;
    }
}
