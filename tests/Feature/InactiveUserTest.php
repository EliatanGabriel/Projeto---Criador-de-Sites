<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('usuário inativo não consegue autenticar', function () {
    $user = User::factory()->create([
        'email' => 'inativo@example.com',
        'password' => Hash::make('Secreta@123'),
        'is_active' => false,
    ]);
    $user->assignRole('OWNER');

    $response = $this->post('/login', [
        'email' => 'inativo@example.com',
        'password' => 'Secreta@123',
    ]);

    $response->assertRedirect();
    $this->assertGuest();
    expect(session()->get('error'))->not->toBeNull();
});

test('usuário desativado perde acesso mesmo com sessão ativa', function () {
    $user = User::factory()->create();
    $user->assignRole('OWNER');

    $this->actingAs($user);
    $user->update(['is_active' => false]);

    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
    $this->assertGuest();
});

test('usuário ativo consegue autenticar e acessa dashboard', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);
    $user->assignRole('OWNER');

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();
});
