<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function ownerUser($overrides = [])
{
    $u = User::factory()->create($overrides);
    $u->assignRole('OWNER');
    return $u;
}

test('owner pode listar usuários', function () {
    $owner = ownerUser();
    $this->actingAs($owner)->get('/team')->assertOk();
});

test('commercial não pode acessar gestão de usuários', function () {
    $c = User::factory()->create();
    $c->assignRole('COMMERCIAL');
    $this->actingAs($c)->get('/team')->assertForbidden();
});

test('developer não pode acessar gestão de usuários', function () {
    $d = User::factory()->create();
    $d->assignRole('DEVELOPER');
    $this->actingAs($d)->get('/team')->assertForbidden();
});

test('owner pode criar usuário interno', function () {
    $owner = ownerUser();
    $response = $this->actingAs($owner)->post('/team', [
        'name' => 'Novo Membro',
        'email' => 'novo@example.com',
        'password' => 'Secreta@123',
        'password_confirmation' => 'Secreta@123',
        'role' => 'COMMERCIAL',
        'is_active' => true,
    ]);
    $response->assertRedirect('/team');
    $this->assertDatabaseHas('users', ['email' => 'novo@example.com']);
    $u = User::where('email', 'novo@example.com')->first();
    expect($u->hasRole('COMMERCIAL'))->toBeTrue();
    expect(Hash::check('Secreta@123', $u->password))->toBeTrue();
});

test('email único é validado', function () {
    $owner = ownerUser();
    User::factory()->create(['email' => 'duplo@example.com']);
    $this->actingAs($owner)->post('/team', [
        'name' => 'Duplo',
        'email' => 'duplo@example.com',
        'password' => 'Secreta@123',
        'password_confirmation' => 'Secreta@123',
        'role' => 'DEVELOPER',
    ])->assertSessionHasErrors('email');
});

test('owner pode editar usuário e alterar perfil', function () {
    $owner = ownerUser();
    $target = User::factory()->create(['name' => 'Alvo', 'email' => 'alvo@example.com']);
    $target->assignRole('COMMERCIAL');
    $this->actingAs($owner)->put("/team/{$target->id}", [
        'name' => 'Alvo Editado',
        'email' => 'alvo2@example.com',
        'role' => 'DEVELOPER',
        'is_active' => true,
    ])->assertRedirect('/team');
    expect($target->fresh()->name)->toBe('Alvo Editado');
    expect($target->fresh()->hasRole('DEVELOPER'))->toBeTrue();
});

test('commercial não pode promover usuário a OWNER por edição direta', function () {
    $owner = ownerUser();
    $commercial = User::factory()->create();
    $commercial->assignRole('COMMERCIAL');
    $target = User::factory()->create();
    $target->assignRole('COMMERCIAL');

    // Commercial nem tem acesso ao endpoint (403 por middleware)
    $this->actingAs($commercial)->put("/team/{$target->id}", [
        'name' => 'X',
        'email' => 'x@example.com',
        'role' => 'OWNER',
    ])->assertForbidden();
});

test('não permite desativar o último OWNER ativo', function () {
    $owner = ownerUser();
    // único OWNER ativo
    $response = $this->actingAs($owner)->post("/team/{$owner->id}/toggle-active");
    // Deve retornar 422 pelo guard
    $response->assertStatus(422);
    expect($owner->fresh()->is_active)->toBeTrue();
});

test('owner pode desativar usuário e desativação respeita proteção de sessões', function () {
    $owner = ownerUser();
    $target = User::factory()->create(['is_active' => true]);
    $target->assignRole('COMMERCIAL');

    $this->actingAs($owner)->post("/team/{$target->id}/toggle-active")->assertRedirect('/team');
    expect($target->fresh()->is_active)->toBeFalse();

    // Agora alvo inativo não consegue acessar dashboard (middleware EnsureUserIsActive)
    // fresh() garante que o user recarregado reflita is_active=false
    $fresh = $target->fresh();
    $response = $this->actingAs($fresh)->get('/dashboard');
    $response->assertRedirect('/login');
});
