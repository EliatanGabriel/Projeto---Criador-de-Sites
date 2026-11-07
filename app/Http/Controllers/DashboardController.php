<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Opportunity;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'active_clients' => Client::where('status', 'ativo')->count(),
            'open_opportunities' => Opportunity::open()->count(),
            'in_negotiation' => Opportunity::where('status', 'em_negociacao')->count(),
            'won' => Opportunity::where('status', 'ganha')->count(),
            'lost' => Opportunity::where('status', 'perdida')->count(),
            'open_estimated_total' => (float) Opportunity::open()->sum('estimated_value'),
        ];

        $recentOpportunities = Opportunity::with(['client:id,name'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'estimated_value', 'created_at', 'client_id']);

        return Inertia::render('dashboard', compact('stats', 'recentOpportunities'));
    }
}
