<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->paginate(15);

        return Inertia::render('team/index', [
            'users' => $users,
            'filters' => request()->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('team/create', [
            'roles' => Role::pluck('name')->toArray(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $role = $data['role'];
        $isActive = $data['is_active'] ?? true;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $isActive,
        ]);
        $user->assignRole($role);

        return redirect()->route('team.index')->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user)
    {
        $user->load('roles');
        return Inertia::render('team/edit', [
            'user' => $user,
            'roles' => Role::pluck('name')->toArray(),
            'currentRole' => $user->roles->first()->name ?? null,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $newRole = $data['role'];
        $targetIsActive = $data['is_active'] ?? $user->is_active;

        $this->guardLastOwner($user, $newRole, $targetIsActive, $request->user());

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $targetIsActive,
        ]);

        $user->syncRoles([$newRole]);

        return redirect()->route('team.index')->with('success', 'Usuário atualizado com sucesso.');
    }

    public function toggleActive(User $user)
    {
        $this->authorize('update', $user);

        $newActive = ! $user->is_active;

        $this->guardLastOwner($user, $user->roles->first()?->name, $newActive, request()->user());

        $user->update(['is_active' => $newActive]);

        $message = $newActive ? 'Usuário ativado.' : 'Usuário desativado. Sessão encerrada por segurança.';

        return redirect()->route('team.index')->with('success', $message);
    }

    protected function guardLastOwner(User $target, ?string $newRole, bool $newActive, User $requester): void
    {
        $isTargetOwner = $target->hasRole('OWNER');
        $willRemainOwner = ($newRole === 'OWNER') && $newActive;

        if ($isTargetOwner && ! $willRemainOwner) {
            $activeOwners = User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'OWNER'))->count();
            if ($activeOwners <= 1) {
                abort(422, 'Não é permitido desativar o último OWNER ativo do sistema.');
            }
        }

        if ($requester->id === $target->id && $newRole === 'OWNER' && $target->hasRole('OWNER')) {
            return;
        }

        if (! $requester->hasRole('OWNER') && $newRole === 'OWNER') {
            abort(403, 'Apenas OWNERS podem atribuir o perfil OWNER.');
        }
    }
}
