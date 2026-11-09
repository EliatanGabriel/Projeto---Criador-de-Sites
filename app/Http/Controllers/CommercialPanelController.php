<?php

namespace App\Http\Controllers;

use App\Domain\Shared\UserScope;
use App\Models\Client;
use App\Models\Demand;
use App\Models\FinancialTransaction;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommercialPanelController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('COMMERCIAL') || $user->hasRole('OWNER'), 403);

        $base = Demand::query();
        UserScope::demands($base, $user);

        $counts = (clone $base)
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as total')
            ->pluck('total', 'status')
            ->toArray();

        $oppQuery = Opportunity::query()->open();
        if (! $user->hasRole('OWNER')) {
            $oppQuery->where('responsible_id', $user->id);
        }
        $oppOpen = (clone $oppQuery)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(estimated_value), 0) as estimated')
            ->first();

        $openOpportunities = $oppQuery
            ->with(['client:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'estimated_value', 'client_id', 'responsible_id', 'created_at']);

        $activeClients = $user->hasRole('OWNER')
            ? Client::where('status', 'ativo')->count()
            : Client::where('status', 'ativo')->whereExists(function ($q) use ($user) {
                $q->selectRaw(1)->from('opportunities')->whereColumn('opportunities.client_id', 'clients.id')->where('opportunities.responsible_id', $user->id);
            })->count();

        $today = now()->toDateString();

        $financeQuery = FinancialTransaction::query()->where('status', '!=', 'cancelado');
        if (! $user->hasRole('OWNER')) {
            $financeQuery->where(function ($q) use ($user) {
                $q->whereExists(function ($sub) use ($user) {
                    $sub->selectRaw(1)->from('opportunities')->whereColumn('opportunities.client_id', 'financial_transactions.client_id')->where('opportunities.responsible_id', $user->id);
                })->orWhereExists(function ($sub) use ($user) {
                    $sub->selectRaw(1)->from('demands')->whereColumn('demands.client_id', 'financial_transactions.client_id')->where('demands.commercial_owner_id', $user->id);
                });
            });
        }
        $finance = (clone $financeQuery)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'receita' AND status IN ('previsto', 'pendente') THEN amount ELSE 0 END), 0) as receita_pendente,
                COALESCE(SUM(CASE WHEN type = 'receita' AND status IN ('previsto', 'pendente') AND due_at < ? THEN amount ELSE 0 END), 0) as receita_vencida,
                COALESCE(SUM(CASE WHEN type = 'receita' AND status = 'recebido' AND paid_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as receita_recebida_mes
            ", [$today, now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->first();

        $financeSummary = [
            'receita_pendente' => (float) ($finance?->receita_pendente ?? 0),
            'receita_vencida' => (float) ($finance?->receita_vencida ?? 0),
            'receita_recebida_mes' => (float) ($finance?->receita_recebida_mes ?? 0),
        ];

        $stats = [
            'draft' => $counts['draft'] ?? 0,
            'submitted' => ($counts['submitted'] ?? 0) + ($counts['under_review'] ?? 0),
            'needs_information' => $counts['needs_information'] ?? 0,
            'approved' => $counts['approved'] ?? 0,
            'rejected' => $counts['rejected'] ?? 0,
            'total' => array_sum($counts),
            'open_opportunities' => (int) ($oppOpen?->total ?? 0),
            'pipeline_estimated' => (float) ($oppOpen?->estimated ?? 0),
            'active_clients' => $activeClients,
        ];

        $mapDemand = function ($demand) use ($user) {
            $waitingDays = $demand->submitted_at ? (int) $demand->submitted_at->diffInDays(now()) : ($demand->created_at ? (int) $demand->created_at->diffInDays(now()) : 0);

            return array_merge($demand->toArray(), [
                'waiting_days' => $waitingDays,
                'can_edit' => $user->can('update', $demand),
                'can_submit' => $user->can('submit', $demand),
            ]);
        };

        $demands = (clone $base)->with(['client:id,name'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get(['id', 'title', 'status', 'priority', 'submitted_at', 'created_at', 'client_id'])
            ->map($mapDemand);

        $needsInfoDemands = (clone $base)->with(['client:id,name'])
            ->where('status', 'needs_information')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'priority', 'submitted_at', 'created_at', 'client_id'])
            ->map($mapDemand);

        $draftDemands = (clone $base)->with(['client:id,name'])
            ->where('status', 'draft')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'priority', 'submitted_at', 'created_at', 'client_id'])
            ->map($mapDemand);

        return Inertia::render('commercial/dashboard', compact('stats', 'demands', 'needsInfoDemands', 'draftDemands', 'openOpportunities', 'financeSummary'));
    }
}
