<?php

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

class CredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('credentials.view') && $user->hasRole('OWNER');
    }

    public function view(User $user, Credential $credential): bool
    {
        return $user->can('credentials.view') && $user->hasRole('OWNER');
    }

    public function create(User $user): bool
    {
        return $user->can('credentials.view') && $user->hasRole('OWNER');
    }

    public function update(User $user, Credential $credential): bool
    {
        return $user->can('credentials.view') && $user->hasRole('OWNER');
    }

    public function delete(User $user, Credential $credential): bool
    {
        return $user->can('credentials.view') && $user->hasRole('OWNER');
    }
}
