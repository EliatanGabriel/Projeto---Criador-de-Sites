<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Credential;
use App\Models\Demand;
use App\Models\Hosting;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteAlert;
use App\Policies\ClientPolicy;
use App\Policies\CredentialPolicy;
use App\Policies\DemandPolicy;
use App\Policies\HostingPolicy;
use App\Policies\OpportunityPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ProjectTemplatePolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Policies\WebsiteAlertPolicy;
use App\Policies\WebsitePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(Demand::class, DemandPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(ProjectTemplate::class, ProjectTemplatePolicy::class);
        Gate::policy(Website::class, WebsitePolicy::class);
        Gate::policy(Hosting::class, HostingPolicy::class);
        Gate::policy(Credential::class, CredentialPolicy::class);
        Gate::policy(WebsiteAlert::class, WebsiteAlertPolicy::class);
    }
}
