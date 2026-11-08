<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function profileUser(string $role): User
{
    $u = User::factory()->create(['name' => "Usuario {$role}", 'last_login_at' => now(), 'is_active' => true]);
    $u->assignRole($role);
    return $u;
}

test('qualquer perfil autenticado vê apenas o próprio perfil em /profile', function (string $role) {
    $user = profileUser($role);
    $other = User::factory()->create(['email' => 'outro@example.com']);

    $this->actingAs($user)->get('/profile')
        ->assertOk()
        ->assertInertia(function (\Inertia\Testing\AssertableInertia $page) use ($user, $role) {
            $page->component('profile')->has('profile', function (\Inertia\Testing\AssertableInertia $page) use ($user, $role) {
                $page->where('id', $user->id)->where('email', $user->email)->where('is_active', true)->etc();
            });
            $page->where('profile.roles.0', $role);
        });
})->with(['OWNER', 'COMMERCIAL', 'DEVELOPER']);

test('visitante sem login não acessa /profile', function () {
    $this->get('/profile')->assertRedirect('/login');
});
