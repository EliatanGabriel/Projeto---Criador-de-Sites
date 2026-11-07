<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCredentialRequest;
use App\Models\Credential;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CredentialController extends Controller
{
    public function store(StoreCredentialRequest $request)
    {
        $data = $request->validated();
        $credential = new Credential([
            'website_id' => $data['website_id'] ?? null,
            'hosting_id' => $data['hosting_id'] ?? null,
            'domain_id' => $data['domain_id'] ?? null,
            'environment_id' => $data['environment_id'] ?? null,
            'type' => $data['type'],
            'label' => $data['label'],
            'username' => $data['username'] ?? null,
            'notes' => $data['notes'] ?? null,
            'last_rotated_at' => now(),
        ]);
        $credential->secret_encrypted = $data['secret'];
        $credential->save();

        $credential->logs()->create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Credencial salva com criptografia.');
    }

    public function reveal(Request $request, Credential $credential): Response
    {
        $this->authorize('view', $credential);

        $request->validate(['password' => ['required', 'current_password']]);

        $secret = $credential->secret_encrypted;

        $credential->logs()->create([
            'user_id' => $request->user()->id,
            'action' => 'revealed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()
            ->json(['secret' => $secret])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function update(Request $request, Credential $credential)
    {
        $this->authorize('update', $credential);
        $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'secret' => ['nullable', 'string', 'min:4', 'max:5000'],
            'notes' => ['nullable', 'string'],
        ]);

        $credential->update([
            'label' => $request->label,
            'username' => $request->username,
            'notes' => $request->notes,
        ]);

        if ($request->filled('secret')) {
            $credential->secret_encrypted = $request->secret;
            $credential->last_rotated_at = now();
            $credential->save();
        }

        $credential->logs()->create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Credencial atualizada.');
    }
}
