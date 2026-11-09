<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('usuário consegue autenticar com credenciais válidas', function () {
    $user = User::factory()->create([
        'email' => 'eliatan@example.com',
        'password' => bcrypt('Secreta@123'),
    ]);
    $user->assignRole('OWNER');

    $response = $this->post('/login', [
        'email' => 'eliatan@example.com',
        'password' => 'Secreta@123',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
});

test('usuário não autenticado não acessa rotas privadas', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('usuário autenticado acessa o dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('OWNER');

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertRedirect('/admin');
});

test('logout encerra a sessão', function () {
    $user = User::factory()->create();
    $user->assignRole('OWNER');

    $response = $this->actingAs($user)->post('/logout');
    $response->assertRedirect('/');
    $this->assertGuest();
});

test('roles são criadas corretamente pelo seeder', function () {
    $this->assertDatabaseHas('roles', ['name' => 'OWNER', 'guard_name' => 'web']);
    $this->assertDatabaseHas('roles', ['name' => 'COMMERCIAL', 'guard_name' => 'web']);
    $this->assertDatabaseHas('roles', ['name' => 'DEVELOPER', 'guard_name' => 'web']);
    $this->assertDatabaseHas('permissions', ['name' => 'view-dashboard', 'guard_name' => 'web']);
});

test('permissões são aplicadas no backend via spatie', function () {
    $owner = User::factory()->create();
    $owner->assignRole('OWNER');
    $commercial = User::factory()->create();
    $commercial->assignRole('COMMERCIAL');
    $developer = User::factory()->create();
    $developer->assignRole('DEVELOPER');

    expect($owner->can('finance.manage'))->toBeTrue()
        ->and($commercial->can('finance.manage'))->toBeFalse()
        ->and($developer->can('finance.manage'))->toBeFalse()
        ->and($commercial->hasRole('COMMERCIAL'))->toBeTrue()
        ->and($developer->hasRole('DEVELOPER'))->toBeTrue();
});

test('usuário com uuid v7 tem id uuid válido', function () {
    $user = User::factory()->create();
    $this->assertTrue(\Illuminate\Support\Str::isUuid($user->id));
});

test('página de login está acessível para convidados', function () {
    $response = $this->get('/login');
    $response->assertOk();
    $response->assertInertia(function ($page) {
        $page->component('auth/login');
    });
});
