<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebsiteRequest;
use App\Http\Requests\UpdateWebsiteRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WebsiteController extends Controller
{
    public function index(Request $request)
    {
        $query = Website::query()
            ->with(['client:id,name', 'technicalOwner:id,name', 'primaryDomain', 'productionEnvironment'])
            ->orderByDesc('created_at');

        $user = $request->user();
        if (! $user->hasRole('OWNER')) {
            $query->whereHas('projects', function ($q) use ($user) {
                if ($user->hasRole('COMMERCIAL')) {
                    $q->whereHas('demand', fn ($qq) => $qq->where('commercial_owner_id', $user->id));
                } elseif ($user->hasRole('DEVELOPER')) {
                    $q->where(function ($qq) use ($user) {
                        $qq->where('technical_owner_id', $user->id)
                            ->orWhereHas('members', fn ($qqq) => $qqq->where('user_id', $user->id))
                            ->orWhereHas('tasks', fn ($qqq) => $qqq->where('assignee_id', $user->id));
                    });
                } else {
                    $q->whereRaw('1=0');
                }
            });
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhereHas('client', fn ($qq) => $qq->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('domains', fn ($qq) => $qq->where('domain', 'like', "%{$s}%"));
            });
        }
        if ($request->status) $query->where('status', $request->status);
        if ($request->client_id) $query->where('client_id', $request->client_id);
        if ($request->type) $query->where('type', $request->type);

        return Inertia::render('sites/index', [
            'sites' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'client_id', 'type']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return Inertia::render('sites/create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'projects' => Project::orderByDesc('created_at')->limit(100)->get(['id', 'title', 'client_id']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreWebsiteRequest $request)
    {
        $data = $request->validated();

        $website = DB::transaction(function () use ($request, $data) {
            $website = Website::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'client_id' => $data['client_id'],
                'type' => $data['type'] ?? null,
                'status' => $data['status'],
                'started_at' => $data['started_at'] ?? null,
                'published_at' => $data['published_at'] ?? null,
                'technical_owner_id' => $data['technical_owner_id'] ?? null,
                'technologies' => $data['technologies'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $projectIds = collect($data['project_ids'] ?? []);
            if (! empty($data['origin_project_id'])) {
                $projectIds->push($data['origin_project_id']);
            }
            foreach ($projectIds->unique() as $projectId) {
                $project = Project::find($projectId);
                if ((string) $project->client_id !== (string) $data['client_id']) {
                    abort(422, 'Projeto vinculado pertence a outro cliente.');
                }
                $isOrigin = ($data['origin_project_id'] ?? null) === $projectId;
                $existingOrigin = $website->websiteProjects()->where('relation_type', 'origin')->exists();
                if ($isOrigin && $existingOrigin) {
                    abort(422, 'Já existe projeto de origem para este site.');
                }
                $website->websiteProjects()->firstOrCreate(
                    ['project_id' => $projectId],
                    ['relation_type' => $isOrigin ? 'origin' : 'evolution', 'registered_at' => now()->toDateString()]
                );
            }

            return $website;
        });

        return redirect()->route('sites.show', $website)->with('success', 'Site cadastrado com sucesso.');
    }

    public function show(Website $website)
    {
        $this->authorize('view', $website);

        $website->load([
            'client:id,name',
            'technicalOwner:id,name',
            'projects:id,title,status,client_id',
            'domains',
            'environments.hosting',
            'repositories.environment',
            'publications.environment',
            'publications.responsible:id,name',
            'maintenanceRecords.responsible:id,name',
            'credentials' => fn ($q) => $q->select('id', 'website_id', 'hosting_id', 'domain_id', 'environment_id', 'type', 'label', 'username', 'notes', 'last_rotated_at', 'created_at', 'updated_at'),
        ]);

        return Inertia::render('sites/show', [
            'site' => $website,
            'healthStates' => \App\Models\WebsiteDomainHealthState::query()->where('website_id', $website->id)->with('domain:id,domain')->get()->map(fn ($s) => [
                'domain_id' => $s->domain_id,
                'domain' => $s->domain->domain ?? null,
                'state' => $s->state,
                'consecutive_failures' => $s->consecutive_failures,
                'cycle_id' => $s->cycle_id,
                'last_checked_at' => $s->last_checked_at,
                'last_ok_at' => $s->last_ok_at,
                'last_recovered_at' => $s->last_recovered_at,
            ]),
            'latestChecks' => \App\Models\WebsiteHealthCheck::query()->where('website_id', $website->id)->with('domain:id,domain')->orderByDesc('checked_at')->limit(20)->get()->map(fn ($c) => [
                'id' => $c->id,
                'domain' => $c->domain->domain ?? null,
                'trigger' => $c->trigger_source,
                'ok' => $c->ok,
                'http_status' => $c->http_status,
                'latency_ms' => $c->latency_ms,
                'ssl_valid' => $c->ssl_valid,
                'ssl_expires_at' => $c->ssl_expires_at,
                'error_code' => $c->error_code,
                'checked_at' => $c->checked_at,
                'cycle_id' => $c->cycle_id,
            ]),
            'hostings' => \App\Models\Hosting::select('id', 'name', 'provider')->orderBy('name')->limit(100)->get(),
            'users' => \App\Models\User::where('is_active', true)->select('id', 'name')->orderBy('name')->limit(100)->get(),
            'canManage' => auth()->user()->can('websites.manage'),
            'canPublish' => auth()->user()->can('publish', $website),
            'canMaintenance' => auth()->user()->can('maintenance', $website),
            'canCredentials' => auth()->user()->can('credentials.view'),
        ]);
    }

    public function edit(Website $website)
    {
        return Inertia::render('sites/edit', [
            'site' => $website,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateWebsiteRequest $request, Website $website)
    {
        $website->update($request->validated());
        return redirect()->route('sites.show', $website)->with('success', 'Site atualizado.');
    }

    public function linkProject(Request $request, Website $website)
    {
        $this->authorize('update', $website);
        $request->validate([
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'relation_type' => ['required', 'in:origin,rework,maintenance,evolution'],
        ]);

        $project = Project::findOrFail($request->project_id);
        if ((string) $project->client_id !== (string) $website->client_id) {
            abort(422, 'Projeto vinculado pertence a outro cliente.');
        }
        if ($request->relation_type === 'origin' && $website->websiteProjects()->where('relation_type', 'origin')->exists()) {
            abort(422, 'Já existe projeto de origem para este site.');
        }

        $website->websiteProjects()->firstOrCreate(
            ['project_id' => $project->id],
            ['relation_type' => $request->relation_type, 'registered_at' => now()->toDateString()]
        );

        return back()->with('success', 'Projeto vinculado ao site.');
    }

    public function runHealthCheck(Website $website, \App\Domain\Websites\Actions\RunSiteHealthCheckAction $action)
    {
        $this->authorize('update', $website);

        $environment = \App\Models\WebsiteEnvironment::query()
            ->where('website_id', $website->id)
            ->where('type', 'production')
            ->where('status', 'ativo')
            ->whereNull('deleted_at')
            ->first();

        $domain = \App\Models\WebsiteDomain::query()
            ->where('website_id', $website->id)
            ->where('status', 'ativo')
            ->whereNull('deleted_at')
            ->orderByDesc('is_primary')
            ->first();

        if ($environment && $domain) {
            $action->checkDomain($domain, $website, $environment, null, 'manual');
        }

        return back()->with('success', 'Verificação de saúde executada.');
    }
}
