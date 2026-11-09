<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $permissions = [
            'view-dashboard',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
            'commercial.view', 'commercial.create', 'commercial.edit',
            'demands.view', 'demands.create', 'demands.edit', 'demands.send',
            'demands.analyze', 'demands.approve', 'demands.convert',
            'projects.view', 'projects.create', 'projects.edit',
            'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.assign',
            'templates.view', 'templates.manage',
            'websites.view', 'websites.manage', 'websites.publications', 'websites.maintenance', 'credentials.view',
            'hostings.view', 'hostings.manage',
            'alerts.view', 'alerts.read', 'alerts.resolve',
            'finance.view', 'finance.manage',
            'reports.view',
            'settings.view', 'settings.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $owner = Role::firstOrCreate(['name' => 'OWNER', 'guard_name' => 'web']);
        $owner->givePermissionTo(Permission::all());

        $commercial = Role::firstOrCreate(['name' => 'COMMERCIAL', 'guard_name' => 'web']);
        $commercial->syncPermissions([
            'view-dashboard',
            'clients.view', 'clients.create', 'clients.edit',
            'commercial.view', 'commercial.create', 'commercial.edit',
            'demands.view', 'demands.create', 'demands.edit', 'demands.send',
            'projects.view', 'reports.view', 'finance.view',
            'websites.view',
            'alerts.view', 'alerts.read',
        ]);

        $developer = Role::firstOrCreate(['name' => 'DEVELOPER', 'guard_name' => 'web']);
        $developer->syncPermissions([
            'view-dashboard',
            'projects.view', 'tasks.view', 'tasks.create', 'tasks.edit',
            'demands.view',
            'templates.view', 'websites.view', 'websites.manage',
            'hostings.view', 'hostings.manage',
            'alerts.view', 'alerts.read', 'alerts.resolve',
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
