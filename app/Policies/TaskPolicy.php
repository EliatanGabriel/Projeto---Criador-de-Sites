<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        if (! $user->can('tasks.view')) return false;
        if ($user->hasRole('OWNER')) return true;
        return $task->assignee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        if ($task->project?->status === 'completed' || $task->project?->status === 'cancelled') return false;
        if ($user->hasRole('OWNER')) return $user->can('tasks.edit');
        if ($user->hasRole('DEVELOPER')) return $task->assignee_id === $user->id && $user->can('tasks.edit');
        return false;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('tasks.edit') && $user->hasRole('OWNER');
    }

    public function assign(User $user): bool
    {
        return $user->can('tasks.assign');
    }
}
