<?php

namespace App\Http\Controllers;

use App\Domain\Projects\Actions\ConvertDemandToProjectAction;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Demand;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()
            ->with(['client:id,name', 'technicalOwner:id,name', 'demand:id,title,status'])
            ->orderByDesc('created_at');

        $user = $request->user();
        if (! $user->hasRole('OWNER')) {
            if ($user->hasRole('DEVELOPER')) {
                $query->where(function ($q) use ($user) {
                    $q->where('technical_owner_id', $user->id)
                        ->orWhereHas('members', fn ($qq) => $qq->where('user_id', $user->id))
                        ->orWhereHas('tasks', fn ($qq) => $qq->where('assignee_id', $user->id));
                });
            } elseif ($user->hasRole('COMMERCIAL')) {
                $query->whereHas('demand', fn ($q) => $q->where('commercial_owner_id', $user->id));
            }
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhereHas('client', fn ($qq) => $qq->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->status) $query->where('status', $request->status);
        if ($request->client_id) $query->where('client_id', $request->client_id);
        if ($request->technical_owner_id) $query->where('technical_owner_id', $request->technical_owner_id);

        return Inertia::render('projects/index', [
            'projects' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'client_id', 'technical_owner_id']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        $approvedDemands = Demand::where('status', 'approved')
            ->whereDoesntHave('project')
            ->with('client:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'client_id', 'status']);

        $templates = \App\Models\ProjectTemplate::with('phases.tasks')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'version', 'is_active']);

        return Inertia::render('projects/create', [
            'demands' => $approvedDemands,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'templates' => $templates,
        ]);
    }

    public function store(StoreProjectRequest $request, ConvertDemandToProjectAction $convert)
    {
        $data = $request->validated();
        $demand = Demand::findOrFail($data['demand_id']);
        $project = $convert($demand, $request->user(), $data['template_id'] ?? null, $data['assignments'] ?? [], $data['excluded_task_ids'] ?? []);
        return redirect()->route('projects.show', $project)->with('success', 'Projeto criado a partir da demanda.');
    }

    public function convert(Demand $demand, ConvertDemandToProjectAction $convert)
    {
        $project = $convert($demand, request()->user());
        return redirect()->route('projects.show', $project)->with('success', 'Demanda convertida em projeto.');
    }

    public function show(Project $project)
    {
        $project->load([
            'client',
            'demand.briefing',
            'demand.attachments',
            'technicalOwner:id,name',
            'tasks' => fn ($q) => $q->orderBy('due_at')->orderBy('created_at'),
            'tasks.assignee:id,name',
            'tasks.creator:id,name',
            'histories.user:id,name',
        ]);
        $progress = $project->progressData();
        if (! auth()->user()->hasRole('OWNER')) {
            $project->contracted_value = null;
        }
        return Inertia::render('projects/show', [
            'project' => $project,
            'progress' => $progress,
            'canManage' => auth()->user()->can('projects.edit'),
            'canUpdateStatus' => auth()->user()->can('updateStatus', $project),
        ]);
    }

    public function edit(Project $project)
    {
        return Inertia::render('projects/edit', [
            'project' => $project,
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $from = $project->status;
        $project->update($request->validated());

        if ($from !== $project->status) {
            $project->histories()->create([
                'user_id' => $request->user()->id,
                'event' => 'status_changed',
                'description' => "Status alterado de {$from} para {$project->status}.",
                'from_status' => $from,
                'to_status' => $project->status,
            ]);
        } else {
            $project->histories()->create([
                'user_id' => $request->user()->id,
                'event' => 'updated',
                'description' => 'Projeto atualizado.',
            ]);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Projeto atualizado.');
    }

    public function updateStatus(Request $request, Project $project)
    {
        $this->authorize('updateStatus', $project);
        $request->validate([
            'status' => 'required|in:planning,in_progress,in_review,blocked,completed,cancelled',
            'blocked_reason' => 'nullable|string|max:1000',
        ]);
        $to = $request->status;

        if ($to === 'blocked' && empty($request->blocked_reason)) {
            return back()->withErrors(['blocked_reason' => 'Justificativa obrigatória para projeto bloqueado.']);
        }
        if ($to === 'completed' && $project->technical_owner_id !== $request->user()->id && ! $request->user()->hasRole('OWNER')) {
            return back()->withErrors(['status' => 'Apenas o responsável técnico ou OWNER pode concluir.']);
        }

        $from = $project->status;
        DB::transaction(function () use ($project, $from, $to, $request) {
            $project->update([
                'status' => $to,
                'blocked_reason' => $to === 'blocked' ? $request->blocked_reason : ($to !== 'blocked' ? null : $project->blocked_reason),
                'completed_at' => $to === 'completed' ? now()->toDateString() : null,
            ]);

            $project->histories()->create([
                'user_id' => $request->user()->id,
                'event' => $to === 'blocked' ? 'blocked' : 'status_changed',
                'description' => $to === 'blocked' ? 'Projeto bloqueado: '.$request->blocked_reason : "Status alterado de {$from} para {$to}.",
                'from_status' => $from,
                'to_status' => $to,
                'metadata' => ['blocked_reason' => $request->blocked_reason],
            ]);
        });

        return redirect()->route('projects.show', $project)->with('success', 'Status atualizado.');
    }
}
