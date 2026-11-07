<?php

namespace App\Policies;

use App\Models\Demand;
use App\Models\User;

class DemandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('demands.view');
    }

    public function view(User $user, Demand $demand): bool
    {
        return $user->can('demands.view');
    }

    public function create(User $user): bool
    {
        return $user->can('demands.create');
    }

    public function update(User $user, Demand $demand): bool
    {
        if (! $user->can('demands.edit')) return false;
        if ($user->hasRole('OWNER')) return $demand->isEditableContent() || $demand->status === 'draft';
        return $demand->isEditableContent();
    }

    public function delete(User $user, Demand $demand): bool
    {
        return $user->can('demands.edit') && $demand->status === 'draft';
    }

    public function submit(User $user, Demand $demand): bool
    {
        if (! $user->can('demands.send')) return false;
        return $demand->status === 'needs_information' || $demand->status === 'draft';
    }

    public function startReview(User $user, Demand $demand): bool
    {
        return $user->can('demands.analyze') && $demand->status === 'submitted';
    }

    public function requestInformation(User $user, Demand $demand): bool
    {
        return $user->can('demands.analyze') && $demand->status === 'under_review';
    }

    public function approve(User $user, Demand $demand): bool
    {
        return $user->can('demands.approve') && $demand->status === 'under_review';
    }

    public function reject(User $user, Demand $demand): bool
    {
        return $user->can('demands.approve') && $demand->status === 'under_review';
    }

    public function cancel(User $user, Demand $demand): bool
    {
        if ($demand->isTerminal()) return false;
        return $user->can('demands.analyze');
    }

    public function download(User $user, Demand $demand): bool
    {
        return $user->can('demands.view');
    }

    public function upload(User $user, Demand $demand): bool
    {
        return $user->can('demands.edit') && $demand->isEditableContent();
    }
}
