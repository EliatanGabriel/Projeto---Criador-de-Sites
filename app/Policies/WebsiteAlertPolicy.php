<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebsiteAlert;

class WebsiteAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('alerts.view');
    }

    public function view(User $user, WebsiteAlert $alert): bool
    {
        if (! $user->can('alerts.view')) return false;
        $website = $alert->website;
        if (! $website) return false;
        return $user->can('view', $website);
    }

    public function read(User $user, WebsiteAlert $alert): bool
    {
        return $this->view($user, $alert) && $user->can('alerts.read');
    }

    public function resolve(User $user, WebsiteAlert $alert): bool
    {
        if (! $this->view($user, $alert)) return false;
        return $user->can('alerts.resolve');
    }
}
