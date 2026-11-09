<?php

namespace App\Policies;

use App\Models\Website;
use App\Models\User;

class WebsitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('websites.view');
    }

    public function view(User $user, Website $website): bool
    {
        if (! $user->can('websites.view')) return false;
        if ($user->hasRole('OWNER')) return true;
        if ($user->hasRole('COMMERCIAL')) {
            return \App\Models\Opportunity::where('client_id', $website->client_id)
                    ->where('responsible_id', $user->id)
                    ->exists()
                || \App\Models\Demand::where('client_id', $website->client_id)
                    ->where('commercial_owner_id', $user->id)
                    ->exists();
        }
        // DEVELOPER: projetos acessíveis
        return $website->projects()->where(function ($q) use ($user) {
            $q->where('projects.technical_owner_id', $user->id)
                ->orWhereHas('members', fn ($qq) => $qq->where('user_id', $user->id))
                ->orWhereHas('tasks', fn ($qq) => $qq->where('assignee_id', $user->id));
        })->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('websites.manage');
    }

    public function update(User $user, Website $website): bool
    {
        return $user->can('websites.manage') && $this->view($user, $website);
    }

    public function delete(User $user, Website $website): bool
    {
        return $user->can('websites.manage') && $this->view($user, $website);
    }

    public function publish(User $user, Website $website): bool
    {
        return ($user->can('websites.manage') || $user->can('websites.publications')) && $this->view($user, $website);
    }

    public function maintenance(User $user, Website $website): bool
    {
        return ($user->can('websites.manage') || $user->can('websites.maintenance')) && $this->view($user, $website);
    }
}
