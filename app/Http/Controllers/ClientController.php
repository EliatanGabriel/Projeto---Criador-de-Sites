<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query()->with('creator:id,name')->orderByDesc('created_at');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('document', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('contact_name', 'like', "%{$s}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $clients = $query->paginate(15)->withQueryString();

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('clients/create');
    }

    public function store(StoreClientRequest $request)
    {
        $client = Client::create($request->validated() + ['created_by' => $request->user()->id]);

        return redirect()->route('clients.show', $client)->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function show(Client $client)
    {
        $this->authorize('view', $client);
        $client->load(['creator:id,name', 'opportunities' => fn ($q) => $q->orderByDesc('created_at')]);

        return Inertia::render('clients/show', ['client' => $client]);
    }

    public function edit(Client $client)
    {
        $this->authorize('update', $client);
        return Inertia::render('clients/edit', ['client' => $client]);
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $this->authorize('update', $client);
        $client->update($request->validated());

        return redirect()->route('clients.show', $client)->with('success', 'Cliente atualizado com sucesso.');
    }
}
