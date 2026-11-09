<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateUserCommand extends Command
{
    protected $signature = 'webstudio:create-user {email : E-mail do usuário} {--name= : Nome completo} {--role= : OWNER|COMMERCIAL|DEVELOPER} {--password= : Senha (se omitida, será solicitada)}';
    protected $description = 'Cria um usuário interno com role definida (provisionamento seguro, sem senha em código versionado)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $roleName = strtoupper($this->option('role') ?? $this->choice('Role', ['OWNER', 'COMMERCIAL', 'DEVELOPER']));

        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            $this->error("Role {$roleName} não encontrada. Execute php artisan db:seed primeiro.");
            return self::FAILURE;
        }

        if (($existing = User::where('email', $email)->first()) !== null) {
            $existing->syncRoles([$roleName]);
            $existing->forceFill(['is_active' => true])->save();
            $this->info("Usuário {$email} já existe; perfil {$roleName} garantido.");
            return self::SUCCESS;
        }

        $name = $this->option('name') ?? $this->ask('Nome completo');
        $password = $this->option('password') ?? $this->secret('Senha');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $this->info("Usuário {$email} criado com role {$roleName} (ID: {$user->id}).");
        return self::SUCCESS;
    }
}
