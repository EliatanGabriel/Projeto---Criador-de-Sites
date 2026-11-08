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

        $stats = [
            'active_clients' => Client::where('status', 'ativo')->count(),
            'open_opportunities' => Opportunity::open()->count(),
            'pending_demands' => Demand::whereIn('status', ['submitted', 'under_review'])->count(),
            'draft_demands' => Demand::where('status', 'draft')->count(),
            'approved_demands' => Demand::where('status', 'approved')->count(),
            'open_estimated_total' => (float) Opportunity::open()->sum('estimated_value'),
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
