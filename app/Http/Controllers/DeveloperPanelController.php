<?php

namespace App\Http\Controllers;

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

        $tasks = Task::query()
            ->with(['project:id,title,status'])
            ->where('assignee_id', $user->id)
            ->orderByDesc('created_at');

        $projects = Project::query()
            ->with(['client:id,name'])
            ->orderByDesc('created_at');

        if (! $user->hasRole('OWNER')) {
            $projects->where(function ($q) use ($user) {
                $q->where('technical_owner_id', $user->id)
                    ->orWhereHas('members', fn ($qq) => $qq->where('user_id', $user->id))
                    ->orWhereHas('tasks', fn ($qq) => $qq->where('assignee_id', $user->id));
            });
        }

        $stats = [
            'todo' => (clone $tasks)->where('status', 'todo')->count(),
            'in_progress' => (clone $tasks)->where('status', 'in_progress')->count(),
            'in_review' => (clone $tasks)->where('status', 'in_review')->count(),
            'blocked' => (clone $tasks)->where('status', 'blocked')->count(),
            'overdue' => (clone $tasks)->whereNotIn('status', ['done', 'cancelled'])->whereNotNull('due_at')->whereDate('due_at', '<', now()->toDateString())->count(),
            'done' => (clone $tasks)->where('status', 'done')->count(),
            'projects' => (clone $projects)->count(),
            'active_projects' => (clone $projects)->whereIn('status', ['in_progress', 'in_review', 'blocked'])->count(),
        ];

        return Inertia::render('developer/dashboard', [
            'stats' => $stats,
            'myTasks' => $tasks->limit(15)->get(['id', 'title', 'status', 'priority', 'due_at', 'blocked_reason', 'project_id', 'created_at']),
            'blockedTasks' => (clone $tasks)->where('status', 'blocked')->limit(10)->get(['id', 'title', 'status', 'priority', 'due_at', 'blocked_reason', 'project_id']),
            'myProjects' => $projects->limit(10)->get(['id', 'title', 'status', 'priority', 'client_id', 'technical_owner_id']),
        ]);
    }
}
