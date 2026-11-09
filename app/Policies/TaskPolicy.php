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
        if ($task->assignee_id === $user->id) return true;
        if ($user->hasRole('DEVELOPER')) {
            $project = $task->project;
            if ($project) {
                return $project->technical_owner_id === $user->id
                    || $project->members()->where('user_id', $user->id)->exists();
            }
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        if ($task->project?->status === 'completed' || $task->project?->status === 'cancelled') return false;
        if ($user->hasRole('OWNER')) return $user->can('tasks.edit');
        if ($user->hasRole('DEVELOPER')) {
            if ($task->assignee_id === $user->id) return $user->can('tasks.edit');
            if ($task->assignee_id === null) {
                $project = $task->project;
                $linked = $project && ($project->technical_owner_id === $user->id || $project->members()->where('user_id', $user->id)->exists());
                return $linked && $user->can('tasks.edit');
            }
        }
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
