<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ActivityLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view-any:activity-log');
    }

    public function view(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('view:activity-log');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:activity-log');
    }

    public function update(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('update:activity-log');
    }

    public function delete(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('delete:activity-log');
    }

    public function restore(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('restore:activity-log');
    }

    public function forceDelete(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('force-delete:activity-log');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force-delete-any:activity-log');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore-any:activity-log');
    }

    public function replicate(AuthUser $authUser, ActivityLog $activityLog): bool
    {
        return $authUser->can('replicate:activity-log');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder:activity-log');
    }

}