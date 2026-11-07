<?php

namespace App\Policies;

use App\Models\ProjectTemplate;
use App\Models\User;

class ProjectTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('templates.view');
    }

    public function view(User $user, ProjectTemplate $template): bool
    {
        return $user->can('templates.view');
    }

    public function create(User $user): bool
    {
        return $user->can('templates.manage');
    }

    public function update(User $user, ProjectTemplate $template): bool
    {
        return $user->can('templates.manage');
    }

    public function delete(User $user, ProjectTemplate $template): bool
    {
        return $user->can('templates.manage');
    }
}
