<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Demand;
use App\Models\FinancialTransaction;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Task;
use App\Models\WebsiteAlert;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPanelController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->hasRole('OWNER'), 403);

        $demandCounts = Demand::query()
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as total')
            ->pluck('total', 'status')
            ->toArray();

        $opportunityOpen = Opportunity::query()->open()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(estimated_value), 0) as estimated')
            ->first();

        $today = now()->toDateString();

        $overdue = Task::query()
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', $today)
            ->count();

        $overdueTasks = Task::query()
            ->with(['project:id,title', 'assignee:id,name'])
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', $today)
            ->orderBy('due_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'priority', 'due_at', 'project_id', 'assignee_id']);

        $activeProjectsQuery = Project::query()
            ->whereIn('status', ['in_progress', 'in_review', 'blocked']);

        $activeProjectsCount = (clone $activeProjectsQuery)->count();

        $activeProjects = $activeProjectsQuery
            ->with(['client:id,name', 'technicalOwner:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'priority', 'client_id', 'technical_owner_id', 'created_at']);

        $finance = FinancialTransaction::query()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'receita' AND status IN ('previsto', 'pendente') THEN amount ELSE 0 END), 0) as receita_pendente,
                COALESCE(SUM(CASE WHEN type = 'receita' AND status IN ('previsto', 'pendente') AND due_at < ? THEN amount ELSE 0 END), 0) as receita_vencida,
                COALESCE(SUM(CASE WHEN type = 'receita' AND status = 'recebido' AND paid_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as receita_recebida_mes,
                COALESCE(SUM(CASE WHEN type = 'despesa' AND status = 'pago' AND paid_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as despesa_paga_mes
            ", [$today, now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString(), now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->where('status', '!=', 'cancelado')
            ->first();

        $openAlerts = WebsiteAlert::query()->where('status', 'open')->count();

        $stats = [
            'active_clients' => Client::where('status', 'ativo')->count(),
            'open_opportunities' => (int) ($opportunityOpen?->total ?? 0),
            'pending_demands' => ($demandCounts['submitted'] ?? 0) + ($demandCounts['under_review'] ?? 0),
            'draft_demands' => $demandCounts['draft'] ?? 0,
            'approved_demands' => $demandCounts['approved'] ?? 0,
            'open_estimated_total' => (float) ($opportunityOpen?->estimated ?? 0),
            'active_projects' => $activeProjectsCount,
            'overdue_tasks' => $overdue,
            'open_alerts' => $openAlerts,
        ];

        $financeSummary = [
            'receita_pendente' => (float) ($finance?->receita_pendente ?? 0),
            'receita_vencida' => (float) ($finance?->receita_vencida ?? 0),
            'receita_recebida_mes' => (float) ($finance?->receita_recebida_mes ?? 0),
            'despesa_paga_mes' => (float) ($finance?->despesa_paga_mes ?? 0),
        ];

        $user = $request->user();

        $pendingDemands = Demand::with(['client:id,name', 'commercialOwner:id,name'])
            ->whereIn('status', ['submitted', 'under_review'])
            ->orderByDesc('submitted_at')
            ->limit(10)
            ->get(['id', 'title', 'status', 'priority', 'submitted_at', 'created_at', 'client_id', 'commercial_owner_id', 'desired_deadline'])
            ->map(function ($demand) use ($user) {
                $waitingDays = $demand->submitted_at ? (int) $demand->submitted_at->diffInDays(now()) : 0;

                return array_merge($demand->toArray(), [
                    'waiting_days' => $waitingDays,
                    'can_start_review' => $user->can('startReview', $demand),
                    'can_approve' => $user->can('approve', $demand),
                    'can_reject' => $user->can('reject', $demand),
                    'can_request_information' => $user->can('requestInformation', $demand),
                    'can_convert' => $user->can('view', $demand) && $demand->status === 'approved',
                ]);
            });

        $recentDemands = Demand::with(['client:id,name', 'commercialOwner:id,name'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'title', 'status', 'priority', 'submitted_at', 'created_at', 'client_id', 'commercial_owner_id']);

        return Inertia::render('admin/dashboard', compact('stats', 'pendingDemands', 'recentDemands', 'overdueTasks', 'activeProjects', 'financeSummary'));
    }
}
