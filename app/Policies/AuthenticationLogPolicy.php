<?php

namespace App\Policies;

use App\Models\User;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuthenticationLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AuthenticationLog $authenticationLog): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuthenticationLog $authenticationLog): bool
    {
        return false;
    }

    public function delete(User $user, AuthenticationLog $authenticationLog): bool
    {
        return true;
    }
}
