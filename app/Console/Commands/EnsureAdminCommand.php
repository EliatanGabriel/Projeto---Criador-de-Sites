<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureAdminCommand extends Command
{
    protected $signature = 'webstudio:ensure-admin';
    protected $description = 'Cria o usuario OWNER inicial a partir de INITIAL_ADMIN_EMAIL/PASSWORD do ambiente (sem credencial versionada)';

    public function handle(): int
    {
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');
        $name = env('INITIAL_ADMIN_NAME', 'Admin');

        (new RolePermissionSeeder)->run();

        if (! $email || ! $password) {
            $this->info('INITIAL_ADMIN_EMAIL/PASSWORD nao definidos; nada a fazer.');

            return self::SUCCESS;
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            $user->syncRoles(['OWNER']);
            $user->forceFill([
                'name' => $name,
                'is_active' => true,
            ])->save();

            $this->info("Admin inicial {$email} ja existe; perfil OWNER garantido.");

            return self::SUCCESS;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);
        $user->assignRole('OWNER');

        $this->info("Admin inicial {$email} criado com role OWNER.");

        return self::SUCCESS;
    }
}
