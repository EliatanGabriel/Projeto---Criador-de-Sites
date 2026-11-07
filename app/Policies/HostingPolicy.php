<?php

namespace App\Policies;

use App\Models\Hosting;
use App\Models\User;

class HostingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hostings.view') || $user->can('websites.view');
    }

    public function view(User $user, Hosting $hosting): bool
    {
        return $user->can('hostings.view') || $user->can('websites.view');
    }

    public function create(User $user): bool
    {
        return $user->can('hostings.manage');
    }

    public function update(User $user, Hosting $hosting): bool
    {
        return $user->can('hostings.manage');
    }

    public function delete(User $user, Hosting $hosting): bool
    {
        return $user->can('hostings.manage');
    }
}
