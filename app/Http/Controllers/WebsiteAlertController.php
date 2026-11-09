<?php

namespace App\Http\Controllers;

use App\Domain\Shared\UserScope;
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
        UserScope::alertWebsites($query, $user);

        if ($request->severity) $query->where('severity', $request->severity);
        if ($request->status) $query->where('status', $request->status);
        if ($request->alert_type) $query->where('alert_type', $request->alert_type);
        if ($request->website_id) $query->where('website_id', $request->website_id);
        if ($request->assigned_to) $query->where('assigned_to', $request->assigned_to);

        $alerts = $query->paginate(15)->withQueryString();

        $openCounts = (clone $query)
            ->reorder()
            ->where('status', 'open')
            ->groupBy('severity')
            ->selectRaw('severity, COUNT(*) as total')
            ->pluck('total', 'severity')
            ->toArray();

        $summary = [
            'open' => array_sum($openCounts),
            'info' => $openCounts['info'] ?? 0,
            'warning' => $openCounts['warning'] ?? 0,
            'critical' => $openCounts['critical'] ?? 0,
        ];

        return Inertia::render('alerts/index', [
            'alerts' => $alerts,
            'filters' => $request->only(['severity', 'status', 'alert_type', 'website_id', 'assigned_to']),
            'summary' => $summary,
            'websites' => Website::select('id', 'name')->orderBy('name')->limit(200)->get(),
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
            'users' => \App\Models\User::where('is_active', true)->select('id', 'name')->orderBy('name')->limit(100)->get(),
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
