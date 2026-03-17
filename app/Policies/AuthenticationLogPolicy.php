<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuthenticationLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view-any:authentication-log');
    }

    public function view(AuthUser $authUser, AuthenticationLog $authenticationLog): bool
    {
        return $authUser->can('view:authentication-log');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:authentication-log');
    }

    public function update(AuthUser $authUser, AuthenticationLog $authenticationLog): bool
    {
        return $authUser->can('update:authentication-log');
    }

    public function delete(AuthUser $authUser, AuthenticationLog $authenticationLog): bool
    {
        return $authUser->can('delete:authentication-log');
    }

    public function restore(AuthUser $authUser, AuthenticationLog $authenticationLog): bool
    {
        return $authUser->can('restore:authentication-log');
    }

    public function forceDelete(AuthUser $authUser, AuthenticationLog $authenticationLog): bool
    {
        return $authUser->can('force-delete:authentication-log');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force-delete-any:authentication-log');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore-any:authentication-log');
    }

    public function replicate(AuthUser $authUser, AuthenticationLog $authenticationLog): bool
    {
        return $authUser->can('replicate:authentication-log');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder:authentication-log');
    }

}