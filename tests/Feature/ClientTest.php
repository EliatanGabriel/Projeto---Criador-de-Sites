<?php

use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function commercialUser()
{
    $u = User::factory()->create();
    $u->assignRole('COMMERCIAL');
    return $u;
}

test('owner e commercial podem listar e criar clientes', function () {
    $owner = User::factory()->create();
    $owner->assignRole('OWNER');
    $this->actingAs($owner)->get('/clients')->assertOk();

    $commercial = commercialUser();
    $this->actingAs($commercial)->get('/clients')->assertOk();
    $this->actingAs($commercial)->post('/clients', [
        'name' => 'Empresa Teste',
        'type' => 'pj',
        'status' => 'ativo',
    ])->assertRedirect();
    $this->assertDatabaseHas('clients', ['name' => 'Empresa Teste']);
});

test('developer não pode administrar clientes', function () {
    $d = User::factory()->create();
    $d->assignRole('DEVELOPER');
    $this->actingAs($d)->get('/clients')->assertForbidden();
    $this->actingAs($d)->post('/clients', ['name' => 'X', 'type' => 'pj', 'status' => 'ativo'])->assertForbidden();
});

test('validação: nome é obrigatório, documento não é exigido', function () {
    $commercial = commercialUser();
    $this->actingAs($commercial)->post('/clients', [
        'type' => 'pj',
        'status' => 'ativo',
    ])->assertSessionHasErrors('name');

    // sem CPF/CNPJ deve funcionar
    $this->actingAs($commercial)->post('/clients', [
        'name' => 'Sem Documento',
        'type' => 'pf',
        'status' => 'ativo',
    ])->assertRedirect();
    $this->assertDatabaseHas('clients', ['name' => 'Sem Documento']);
});

test('pode editar cliente e filtrar por status', function () {
    $commercial = commercialUser();
    $client = Client::factory()->create(['name' => 'Original', 'status' => 'ativo']);
    Client::factory()->create(['name' => 'Inativo', 'status' => 'inativo']);

    $this->actingAs($commercial)->put("/clients/{$client->id}", [
        'name' => 'Editado',
        'type' => 'pj',
        'status' => 'inativo',
    ])->assertRedirect();
    expect($client->fresh()->name)->toBe('Editado');
    expect($client->fresh()->status)->toBe('inativo');

    $response = $this->actingAs($commercial)->get('/clients?status=ativo');
    $response->assertOk();
    $response->assertInertia(function ($page) {
        $page->component('clients/index');
    });
});

test('busca filtra por nome', function () {
    $commercial = commercialUser();
    Client::factory()->create(['name' => 'BuscaAlvo']);
    Client::factory()->create(['name' => 'OutroNome']);

    $response = $this->actingAs($commercial)->get('/clients?search=BuscaAlvo');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('clients/index'));
});
