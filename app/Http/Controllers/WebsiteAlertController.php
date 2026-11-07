<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\WebsiteAlert;
use App\Models\WebsiteAlertRead;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WebsiteAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = WebsiteAlert::query()
            ->with(['website:id,name,status', 'assignee:id,name'])
            ->orderByDesc('due_at');

        $user = $request->user();
        if (! $user->hasRole('OWNER')) {
            $query->whereIn('website_id', function ($q) use ($user) {
                $q->select('id')->from('websites')->whereNull('deleted_at')
                    ->where(function ($qq) use ($user) {
                        if ($user->hasRole('COMMERCIAL')) {
                            $qq->whereIn('client_id', function ($qqq) use ($user) {
                                $qqq->select('client_id')->from('opportunities')->where('responsible_id', $user->id)
                                    ->union(function ($qqq) use ($user) {
                                        $qqq->select('client_id')->from('demands')->where('commercial_owner_id', $user->id);
                                    });
                            });
                        } elseif ($user->hasRole('DEVELOPER')) {
                            $qq->whereExists(function ($qqq) use ($user) {
                                $qqq->select(\DB::raw(1))->from('website_projects AS wp')
                                    ->join('projects AS p', 'p.id', '=', 'wp.project_id')
                                    ->whereColumn('wp.website_id', 'websites.id')
                                    ->where(function ($w) use ($user) {
                                        $w->where('p.technical_owner_id', $user->id)
                                            ->orWhereHas('members', fn ($ww) => $ww->where('user_id', $user->id) ?? true);
                                    });
                            });
                        } else {
                            $qq->whereRaw('1=0');
                        }
                    });
            });
        }

        if ($request->severity) $query->where('severity', $request->severity);
        if ($request->status) $query->where('status', $request->status);
        if ($request->alert_type) $query->where('alert_type', $request->alert_type);
        if ($request->website_id) $query->where('website_id', $request->website_id);
        if ($request->assigned_to) $query->where('assigned_to', $request->assigned_to);

        $alerts = $query->paginate(15)->withQueryString();
        $summary = [
            'open' => (clone $query)->where('status', 'open')->count(),
            'info' => (clone $query)->where('severity', 'info')->where('status', 'open')->count(),
            'warning' => (clone $query)->where('severity', 'warning')->where('status', 'open')->count(),
            'critical' => (clone $query)->where('severity', 'critical')->where('status', 'open')->count(),
        ];

        return Inertia::render('alerts/index', [
            'alerts' => $alerts,
            'filters' => $request->only(['severity', 'status', 'alert_type', 'website_id', 'assigned_to']),
            'summary' => $summary,
            'websites' => Website::select('id', 'name')->orderBy('name')->get(),
            'users' => \App\Models\User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(WebsiteAlert $alert)
    {
        $this->authorize('view', $alert);
        $alert->load(['website', 'assignee:id,name', 'activities.user:id,name']);

        $read = $alert->reads()->where('user_id', auth()->id())->first();

        return Inertia::render('alerts/show', [
            'alert' => $alert,
            'isRead' => (bool) $read,
            'canRead' => auth()->user()->can('read', $alert),
            'canResolve' => auth()->user()->can('resolve', $alert),
        ]);
    }

    public function read(WebsiteAlert $alert)
    {
        $this->authorize('read', $alert);
        $alert->reads()->firstOrCreate(
            ['user_id' => auth()->id()],
            ['read_at' => now()]
        );
        $alert->activities()->create([
            'user_id' => auth()->id(),
            'event' => 'read',
            'description' => 'Alerta marcado como lido.',
            'created_at' => now(),
        ]);
        return back()->with('success', 'Alerta marcado como lido.');
    }

    public function resolve(WebsiteAlert $alert)
    {
        $this->authorize('resolve', $alert);
        if ($alert->status !== 'open') {
            abort(422, 'Apenas alertas abertos podem ser resolvidos.');
        }
        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);
        $alert->activities()->create([
            'user_id' => auth()->id(),
            'event' => 'resolved',
            'description' => 'Alerta resolvido.',
            'created_at' => now(),
        ]);
        return back()->with('success', 'Alerta resolvido.');
    }

    public function dismiss(WebsiteAlert $alert)
    {
        $this->authorize('resolve', $alert);
        if ($alert->status !== 'open') {
            abort(422, 'Apenas alertas abertos podem ser dispensados.');
        }
        $alert->update([
            'status' => 'dismissed',
            'dismissed_at' => now(),
            'dismissed_by' => auth()->id(),
        ]);
        $alert->activities()->create([
            'user_id' => auth()->id(),
            'event' => 'dismissed',
            'description' => 'Alerta dispensado.',
            'created_at' => now(),
        ]);
        return back()->with('success', 'Alerta dispensado.');
    }

    public function assign(Request $request, WebsiteAlert $alert)
    {
        $this->authorize('read', $alert);
        $request->validate(['assigned_to' => ['nullable', 'uuid', 'exists:users,id']]);
        $alert->update(['assigned_to' => $request->assigned_to]);
        $alert->activities()->create([
            'user_id' => auth()->id(),
            'event' => 'assigned',
            'description' => 'Responsável atualizado.',
            'created_at' => now(),
        ]);
        return back()->with('success', 'Responsável atualizado.');
    }
}
