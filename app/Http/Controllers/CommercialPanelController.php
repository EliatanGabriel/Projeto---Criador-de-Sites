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

        $query = Demand::query()
            ->with(['client:id,name', 'opportunity:id,title'])
            ->orderByDesc('created_at');

        if (! $user->hasRole('OWNER')) {
            $query->where(function ($q) use ($user) {
                $q->where('commercial_owner_id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        $stats = [
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'submitted' => (clone $query)->whereIn('status', ['submitted', 'under_review'])->count(),
            'needs_information' => (clone $query)->where('status', 'needs_information')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'total' => (clone $query)->count(),
        ];

        $demands = $query->limit(15)->get(['id', 'title', 'status', 'priority', 'submitted_at', 'created_at', 'client_id', 'opportunity_id']);

        return Inertia::render('commercial/dashboard', compact('stats', 'demands'));
    }
}
