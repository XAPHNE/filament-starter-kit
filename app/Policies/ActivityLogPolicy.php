<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ActivityLog $activityLog): bool
    {
        return false;
    }

    public function delete(User $user, ActivityLog $activityLog): bool
    {
        return true;
    }
}
