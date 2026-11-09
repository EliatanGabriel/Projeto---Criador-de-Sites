<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskBlocked;
use App\Notifications\TaskCompleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::query()
            ->with(['project:id,title,status', 'assignee:id,name', 'creator:id,name'])
            ->orderByDesc('created_at');

        $user = $request->user();
        if (! $user->hasRole('OWNER')) {
            if ($user->hasRole('DEVELOPER')) {
                $query->where(function ($q) use ($user) {
                    $q->where('assignee_id', $user->id)
                        ->orWhereHas('project', function ($pq) use ($user) {
                            $pq->where('technical_owner_id', $user->id)
                                ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
                        });
                });
            } else {
                $query->where('assignee_id', $user->id);
            }
        }

        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%");
        }
        if ($request->project_id) $query->where('project_id', $request->project_id);
        if ($request->responsible_id) $query->where('assignee_id', $request->responsible_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->priority) $query->where('priority', $request->priority);

        return Inertia::render('tasks/index', [
            'tasks' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'project_id', 'responsible_id', 'status', 'priority']),
            'projects' => $this->accessibleProjects($user),
        ]);
    }

    public function myTasks(Request $request)
    {
        $user = $request->user();
        $scope = function ($q) use ($user) {
            if ($user->hasRole('OWNER') || $user->hasRole('COMMERCIAL')) {
                $q->where('assignee_id', $user->id);
                return;
            }
            $q->where(function ($qq) use ($user) {
                $qq->where('assignee_id', $user->id)
                    ->orWhereHas('project', function ($pq) use ($user) {
                        $pq->where('technical_owner_id', $user->id)
                            ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
                    });
            });
        };

        $query = Task::query()
            ->with(['project:id,title,status', 'assignee:id,name'])
            ->where($scope)
            ->orderByDesc('created_at');

        if ($request->status) $query->where('status', $request->status);
        if ($request->priority) $query->where('priority', $request->priority);

        $tasks = $query->paginate(15)->withQueryString();

        $boardQuery = Task::query()
            ->with(['project:id,title,status'])
            ->where($scope)
            ->orderByDesc('created_at');

        $board = $boardQuery->get(['id', 'title', 'status', 'priority', 'due_at', 'blocked_reason', 'project_id', 'created_at'])
            ->groupBy('status');

        return Inertia::render('tasks/my-tasks', [
            'tasks' => $tasks,
            'board' => $board,
            'filters' => $request->only(['status', 'priority']),
        ]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorize('update', $task);
        $request->validate([
            'status' => ['required', 'in:todo,in_progress,in_review,blocked,done,cancelled'],
            'blocked_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $isOwner = $user->hasRole('OWNER');

        if ($task->project?->isClosed()) {
            return back()->withErrors(['status' => 'Projeto encerrado: tarefas não podem ser alteradas.']);
        }

        if (! $isOwner && $task->assignee_id !== null && (string) $task->assignee_id !== (string) $user->id) {
            abort(403, 'Só é possível atualizar tarefas atribuídas a você.');
        }

        $status = $request->status;
        $blockedReason = $request->blocked_reason ?? $task->blocked_reason;

        if ($status === 'blocked' && empty($blockedReason)) {
            return back()->withErrors(['blocked_reason' => 'Justificativa obrigatória para tarefas bloqueadas.']);
        }

        $from = $task->status;

        DB::transaction(function () use ($task, $status, $blockedReason, $user, $from) {
            $updates = ['status' => $status];
            if ($task->assignee_id === null) {
                $updates['assignee_id'] = $user->id;
            }
            if ($status === 'done') {
                $updates['completed_at'] = now();
                $updates['completed_by'] = $user->id;
            } else {
                $updates['completed_at'] = null;
                $updates['completed_by'] = null;
            }
            $updates['blocked_reason'] = $status === 'blocked' ? $blockedReason : null;

            $task->update($updates);

            $task->histories()->create([
                'user_id' => $user->id,
                'event' => $from !== $task->status ? 'status_changed' : 'updated',
                'description' => $from !== $task->status ? "Status alterado de {$from} para {$task->status}." : 'Tarefa atualizada.',
                'from_status' => $from,
                'to_status' => $task->status,
            ]);
        });

        if ($task->status === 'blocked') {
            User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'OWNER'))->get()->each(fn ($o) => $o->notify(new TaskBlocked($task)));
        }
        if ($task->status === 'done') {
            User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'OWNER'))->get()->each(fn ($o) => $o->notify(new TaskCompleted($task)));
        }

        return back()->with('success', 'Status atualizado.');
    }

    public function create(Request $request)
    {
        return Inertia::render('tasks/create', [
            'projects' => $this->accessibleProjects($request->user())->get(),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'projectId' => $request->query('project_id'),
        ]);
    }

    public function store(StoreTaskRequest $request)
    {
        $data = $request->validated();
        $project = Project::findOrFail($data['project_id']);
        if (! $request->user()->hasRole('OWNER')) {
            if (! $request->user()->can('view', $project)) {
                abort(403);
            }
            if (($data['assignee_id'] ?? null) && (string) $data['assignee_id'] !== (string) $request->user()->id) {
                abort(403, 'DEVELOPER não pode atribuir tarefas a terceiros.');
            }
        }
        if ($project->isClosed()) {
            return back()->withErrors(['project_id' => 'Projeto encerrado não aceita novas tarefas.']);
        }
        if ($data['status'] === 'blocked' && empty($data['blocked_reason'])) {
            return back()->withErrors(['blocked_reason' => 'Justificativa obrigatória para tarefas bloqueadas.']);
        }

        $task = DB::transaction(function () use ($request, $data, $project) {
            $task = Task::create($data + [
                'created_by' => $request->user()->id,
                'assignee_id' => $data['assignee_id'] ?? $request->user()->id,
                'completed_at' => $data['status'] === 'done' ? now() : null,
                'completed_by' => $data['status'] === 'done' ? $request->user()->id : null,
            ]);

            $task->histories()->create([
                'user_id' => $request->user()->id,
                'event' => 'created',
                'description' => 'Tarefa criada.',
            ]);

            return $task;
        });

        if ($task->assignee_id) {
            User::find($task->assignee_id)?->notify(new TaskAssigned($task));
        }

        return redirect()->route('tasks.show', $task)->with('success', 'Tarefa criada.');
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);
        $task->load(['project.client:id,name', 'assignee:id,name', 'creator:id,name', 'histories.user:id,name', 'comments.user:id,name']);
        return Inertia::render('tasks/show', ['task' => $task]);
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);
        return Inertia::render('tasks/edit', [
            'task' => $task,
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $user = $request->user();
        $data = $request->validated();

        if ($task->project?->isClosed()) {
            return back()->withErrors(['status' => 'Projeto encerrado: tarefas não podem ser alteradas.']);
        }

        $isOwner = $user->hasRole('OWNER');
        if (! $isOwner) {
            // DEVELOPER: só próprias ou não atribuídas em projeto vinculado (claim), sem reatribuir
            if ($task->assignee_id !== null && (string) $task->assignee_id !== (string) $user->id) {
                abort(403, 'Só é possível atualizar tarefas atribuídas a você.');
            }
            if (isset($data['assignee_id']) && $data['assignee_id'] !== $task->assignee_id) {
                abort(403, 'DEVELOPER não pode atribuir tarefas.');
            }
            unset($data['assignee_id']);
            if ($task->assignee_id === null) {
                $data['assignee_id'] = $user->id;
            }
        }

        if ($data['status'] === 'blocked' && empty($data['blocked_reason'] ?? $task->blocked_reason)) {
            return back()->withErrors(['blocked_reason' => 'Justificativa obrigatória para tarefas bloqueadas.']);
        }

        $from = $task->status;
        $fromAssignee = $task->assignee_id;

        DB::transaction(function () use ($task, $data, $user, $from, $fromAssignee) {
            $updates = $data;
            if (($updates['status'] ?? null) === 'done') {
                $updates['completed_at'] = now();
                $updates['completed_by'] = $user->id;
            }
            if (isset($updates['status']) && $updates['status'] !== 'blocked') {
                $updates['blocked_reason'] = null;
            }
            $task->update($updates);

            $task->histories()->create([
                'user_id' => $user->id,
                'event' => $from !== $task->status ? 'status_changed' : 'updated',
                'description' => $from !== $task->status ? "Status alterado de {$from} para {$task->status}." : 'Tarefa atualizada.',
                'from_status' => $from,
                'to_status' => $task->status,
            ]);

            if ((string) $fromAssignee !== (string) $task->assignee_id) {
                $task->histories()->create([
                    'user_id' => $user->id,
                    'event' => 'reassigned',
                    'description' => 'Responsável alterado.',
                    'metadata' => ['from' => $fromAssignee, 'to' => $task->assignee_id],
                ]);
            }
        });

        if ($task->status === 'blocked') {
            User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'OWNER'))->get()->each(fn ($o) => $o->notify(new TaskBlocked($task)));
        }
        if ($task->status === 'done') {
            User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'OWNER'))->get()->each(fn ($o) => $o->notify(new TaskCompleted($task)));
        }
        if ((string) $fromAssignee !== (string) ($task->assignee_id ?? '')) {
            User::find($task->assignee_id)?->notify(new TaskAssigned($task));
        }

        return redirect()->route('tasks.show', $task)->with('success', 'Tarefa atualizada.');
    }

    public function comment(Request $request, Task $task)
    {
        $this->authorize('view', $task);
        $request->validate(['comment' => 'required|string|max:2000']);
        $task->comments()->create(['user_id' => $request->user()->id, 'comment' => $request->comment]);
        $task->histories()->create([
            'user_id' => $request->user()->id,
            'event' => 'commented',
            'description' => $request->comment,
        ]);
        return back()->with('success', 'Comentário adicionado.');
    }

    protected function accessibleProjects($user)
    {
        if ($user->hasRole('OWNER')) return Project::orderBy('title');
        if ($user->hasRole('DEVELOPER')) {
            return Project::where(function ($q) use ($user) {
                $q->where('technical_owner_id', $user->id)
                    ->orWhereHas('members', fn ($qq) => $qq->where('user_id', $user->id))
                    ->orWhereHas('tasks', fn ($qq) => $qq->where('assignee_id', $user->id));
            })->orderBy('title');
        }
        return Project::whereRaw('1 = 0')->orderBy('title');
    }
}
