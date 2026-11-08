<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        if (! $user->can('projects.view')) return false;
        if ($user->hasRole('OWNER')) return true;
        if ($user->hasRole('COMMERCIAL')) {
            return $project->client && $project->client->opportunities()->where('responsible_id', $user->id)->exists()
                || \App\Models\Demand::where('id', $project->demand_id)->where('commercial_owner_id', $user->id)->exists();
        }
        // DEVELOPER: só projetos onde é membro ou técnico ou tem tarefa
        return $project->technical_owner_id === $user->id
            || $project->members()->where('user_id', $user->id)->exists()
            || $project->tasks()->where('assignee_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('projects.create') && $user->hasRole('OWNER');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('projects.edit');
    }

    public function updateStatus(User $user, Project $project): bool
    {
        if ($user->can('projects.edit')) {
            return true;
        }
        if (! $user->hasRole('DEVELOPER')) {
            return false;
        }

        return $this->view($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('projects.edit');
    }
}
