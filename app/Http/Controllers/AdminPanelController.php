<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Demand;
use App\Models\Opportunity;
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

        $stats = [
            'active_clients' => Client::where('status', 'ativo')->count(),
            'open_opportunities' => (int) ($opportunityOpen?->total ?? 0),
            'pending_demands' => ($demandCounts['submitted'] ?? 0) + ($demandCounts['under_review'] ?? 0),
            'draft_demands' => $demandCounts['draft'] ?? 0,
            'approved_demands' => $demandCounts['approved'] ?? 0,
            'open_estimated_total' => (float) ($opportunityOpen?->estimated ?? 0),
        ];

        $pendingDemands = Demand::with(['client:id,name', 'commercialOwner:id,name'])
            ->whereIn('status', ['submitted', 'under_review'])
            ->orderByDesc('submitted_at')
            ->limit(10)
            ->get(['id', 'title', 'status', 'priority', 'submitted_at', 'created_at', 'client_id', 'commercial_owner_id']);

        $recentDemands = Demand::with(['client:id,name', 'commercialOwner:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'title', 'status', 'priority', 'submitted_at', 'created_at', 'client_id', 'commercial_owner_id']);

        return Inertia::render('admin/dashboard', compact('stats', 'pendingDemands', 'recentDemands'));
    }
}
