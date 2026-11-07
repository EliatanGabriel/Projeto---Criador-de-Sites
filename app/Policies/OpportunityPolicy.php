<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('commercial.view');
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        return $user->can('commercial.view');
    }

    public function create(User $user): bool
    {
        return $user->can('commercial.create');
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        return $user->can('commercial.edit');
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        return $user->can('commercial.edit');
    }
}
