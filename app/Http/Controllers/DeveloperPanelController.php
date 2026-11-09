<?php

namespace App\Http\Controllers;

use App\Domain\Shared\UserScope;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeveloperPanelController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('DEVELOPER') || $user->hasRole('OWNER'), 403);

        $today = now()->toDateString();

        $scopeTasks = fn ($q) => UserScope::tasks($q, $user);

        $taskCounts = Task::query()
            ->where($scopeTasks)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $overdue = Task::query()
            ->where($scopeTasks)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', $today)
            ->count();

        $projectsQuery = Project::query();
        UserScope::projects($projectsQuery, $user);

        $projectTotals = (clone $projectsQuery)
            ->selectRaw("COUNT(*) as total, COALESCE(SUM(CASE WHEN status IN ('in_progress', 'in_review', 'blocked') THEN 1 ELSE 0 END), 0) as active")
            ->first();

        $stats = [
            'todo' => $taskCounts['todo'] ?? 0,
            'in_progress' => $taskCounts['in_progress'] ?? 0,
            'in_review' => $taskCounts['in_review'] ?? 0,
            'blocked' => $taskCounts['blocked'] ?? 0,
            'overdue' => $overdue,
            'done' => $taskCounts['done'] ?? 0,
            'projects' => (int) ($projectTotals?->total ?? 0),
            'active_projects' => (int) ($projectTotals?->active ?? 0),
        ];

        $tasksQuery = Task::query()
            ->with(['project:id,title,status'])
            ->where($scopeTasks)
            ->orderByDesc('created_at');

        return Inertia::render('developer/dashboard', [
            'stats' => $stats,
            'myTasks' => (clone $tasksQuery)->limit(15)->get(['id', 'title', 'status', 'priority', 'due_at', 'blocked_reason', 'project_id', 'created_at']),
            'blockedTasks' => (clone $tasksQuery)->where('status', 'blocked')->limit(10)->get(['id', 'title', 'status', 'priority', 'due_at', 'blocked_reason', 'project_id']),
            'myProjects' => $projectsQuery
                ->with(['client:id,name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['id', 'title', 'status', 'priority', 'client_id', 'technical_owner_id']),
        ]);
    }
}
