<?php

namespace App\Http\Controllers;

use App\Models\Demand;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommercialPanelController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('COMMERCIAL') || $user->hasRole('OWNER'), 403);

        $base = Demand::query();
        if (! $user->hasRole('OWNER')) {
            $base->where(function ($q) use ($user) {
                $q->where('commercial_owner_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        $counts = (clone $base)
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as total')
            ->pluck('total', 'status')
            ->toArray();

        $stats = [
            'draft' => $counts['draft'] ?? 0,
            'submitted' => ($counts['submitted'] ?? 0) + ($counts['under_review'] ?? 0),
            'needs_information' => $counts['needs_information'] ?? 0,
            'approved' => $counts['approved'] ?? 0,
            'total' => array_sum($counts),
        ];

        $demands = $base->with(['client:id,name'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get(['id', 'title', 'status', 'priority', 'submitted_at', 'created_at', 'client_id']);

        return Inertia::render('commercial/dashboard', compact('stats', 'demands'));
    }
}
