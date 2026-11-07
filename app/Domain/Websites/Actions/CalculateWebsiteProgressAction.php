<?php

namespace App\Domain\Websites\Actions;

use App\Models\Task;
use App\Models\Website;

class CalculateWebsiteProgressAction
{
    public function __invoke(Website $website): array
    {
        $projectIds = $website->websiteProjects()->pluck('project_id')->all();
        if (empty($projectIds)) {
            return [
                'status' => 'no_tasks',
                'progress' => null,
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'remaining_tasks' => 0,
            ];
        }

        $tasks = Task::query()
            ->join('projects AS p', 'p.id', '=', 'tasks.project_id')
            ->whereIn('tasks.project_id', $projectIds)
            ->whereNull('tasks.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('p.status', '!=', 'cancelled')
            ->where('tasks.status', '!=', 'cancelled')
            ->select('tasks.id', 'tasks.status')
            ->distinct()
            ->get(['tasks.id', 'tasks.status']);

        $total = $tasks->count();
        if ($total === 0) {
            return [
                'status' => 'no_tasks',
                'progress' => null,
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'remaining_tasks' => 0,
            ];
        }

        $completed = $tasks->where('status', 'done')->count();
        $percentage = (int) round(($completed / $total) * 100);

        return [
            'status' => 'available',
            'progress' => $percentage,
            'total_tasks' => $total,
            'completed_tasks' => $completed,
            'remaining_tasks' => $total - $completed,
            'by_status' => $tasks->groupBy('status')->map->count()->toArray(),
        ];
    }
}
