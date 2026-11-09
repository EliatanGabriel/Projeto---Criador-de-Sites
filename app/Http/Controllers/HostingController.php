<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHostingRequest;
use App\Models\Hosting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HostingController extends Controller
{
    public function index(Request $request)
    {
        $query = Hosting::query()->orderBy('name');
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('provider', 'like', "%{$s}%");
            });
        }
        if ($request->status) $query->where('status', $request->status);

        return Inertia::render('hostings/index', [
            'hostings' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('hostings/create');
    }

    public function store(StoreHostingRequest $request)
    {
        $hosting = Hosting::create($request->validated() + ['notes' => $request->notes]);
        return redirect()->route('hostings.show', $hosting)->with('success', 'Hospedagem cadastrada.');
    }

    public function show(Hosting $hosting)
    {
        $hosting->load(['environments.website:id,name']);
        return Inertia::render('hostings/show', [
            'hosting' => $hosting,
            'canCredentials' => auth()->user()->can('credentials.view'),
        ]);
    }

    public function edit(Hosting $hosting)
    {
        $this->authorize('update', $hosting);
        return Inertia::render('hostings/edit', ['hosting' => $hosting]);
    }

    public function update(Request $request, Hosting $hosting)
    {
        $this->authorize('update', $hosting);
        $request->validate([
            'provider' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'plan' => ['nullable', 'string', 'max:100'],
            'account_identifier' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:ativo,suspenso,cancelado'],
            'notes' => ['nullable', 'string'],
        ]);
        $hosting->update($request->validated());
        return redirect()->route('hostings.show', $hosting)->with('success', 'Hospedagem atualizada.');
    }
}
