<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = Opportunity::query()
            ->with(['client:id,name', 'responsible:id,name'])
            ->orderByDesc('created_at');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('contact_name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->responsible_id) {
            $query->where('responsible_id', $request->responsible_id);
        }

        $opportunities = $query->paginate(15)->withQueryString();

        return Inertia::render('opportunities/index', [
            'opportunities' => $opportunities,
            'filters' => $request->only(['search', 'status', 'responsible_id']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return Inertia::render('opportunities/create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreOpportunityRequest $request)
    {
        $data = $request->validated();
        $opportunity = Opportunity::create($data + ['created_by' => $request->user()->id]);
        $opportunity->activities()->create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'description' => 'Oportunidade criada.',
        ]);

        return redirect()->route('opportunities.show', $opportunity)->with('success', 'Oportunidade criada com sucesso.');
    }

    public function show(Opportunity $opportunity)
    {
        $this->authorize('view', $opportunity);
        $opportunity->load(['client', 'responsible:id,name', 'creator:id,name', 'activities.user:id,name']);

        return Inertia::render('opportunities/show', ['opportunity' => $opportunity]);
    }

    public function edit(Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);
        return Inertia::render('opportunities/edit', [
            'opportunity' => $opportunity,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);
        $data = $request->validated();
        $fromStatus = $opportunity->status;
        $opportunity->update($data);

        $opportunity->activities()->create([
            'user_id' => $request->user()->id,
            'action' => $fromStatus !== $data['status'] ? 'status_changed' : 'updated',
            'description' => $fromStatus !== $data['status']
                ? "Status alterado de {$fromStatus} para {$data['status']}."
                : 'Oportunidade atualizada.',
            'from_status' => $fromStatus,
            'to_status' => $data['status'],
        ]);

        return redirect()->route('opportunities.show', $opportunity)->with('success', 'Oportunidade atualizada.');
    }

    public function updateStatus(Request $request, Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);
        $request->validate(['status' => 'required|in:nova,em_contato,em_negociacao,aguardando_cliente,ganha,perdida,cancelada']);
        $from = $opportunity->status;
        $opportunity->update(['status' => $request->status]);
        $opportunity->activities()->create([
            'user_id' => $request->user()->id,
            'action' => 'status_changed',
            'description' => "Status alterado de {$from} para {$request->status}.",
            'from_status' => $from,
            'to_status' => $request->status,
        ]);

        return back()->with('success', 'Status atualizado.');
    }
}
