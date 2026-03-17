<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tier;
use Illuminate\Auth\Access\HandlesAuthorization;

class TierPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view-any:tier');
    }

    public function view(AuthUser $authUser, Tier $tier): bool
    {
        return $authUser->can('view:tier');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:tier');
    }

    public function update(AuthUser $authUser, Tier $tier): bool
    {
        return $authUser->can('update:tier');
    }

    public function delete(AuthUser $authUser, Tier $tier): bool
    {
        return $authUser->can('delete:tier');
    }

    public function restore(AuthUser $authUser, Tier $tier): bool
    {
        return $authUser->can('restore:tier');
    }

    public function forceDelete(AuthUser $authUser, Tier $tier): bool
    {
        return $authUser->can('force-delete:tier');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force-delete-any:tier');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore-any:tier');
    }

    public function replicate(AuthUser $authUser, Tier $tier): bool
    {
        return $authUser->can('replicate:tier');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder:tier');
    }

}