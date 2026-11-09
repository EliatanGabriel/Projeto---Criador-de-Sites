<?php

use App\Models\Client;
use App\Models\Opportunity;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function commercialUser2()
{
    $u = User::factory()->create();
    $u->assignRole('COMMERCIAL');
    return $u;
}

test('owner e commercial podem gerenciar oportunidades', function () {
    $owner = User::factory()->create();
    $owner->assignRole('OWNER');
    $this->actingAs($owner)->get('/opportunities')->assertOk();

    $commercial = commercialUser2();
    $this->actingAs($commercial)->get('/opportunities')->assertOk();

    $response = $this->actingAs($commercial)->post('/opportunities', [
        'title' => 'Nova oportunidade',
        'status' => 'nova',
        'estimated_value' => 5000,
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('opportunities', ['title' => 'Nova oportunidade', 'status' => 'nova']);
});

test('developer não pode administrar negociações comerciais', function () {
    $d = User::factory()->create();
    $d->assignRole('DEVELOPER');
    $this->actingAs($d)->get('/opportunities')->assertForbidden();
    $opp = Opportunity::factory()->create();
    $this->actingAs($d)->put("/opportunities/{$opp->id}", ['title' => 'X', 'status' => 'nova'])->assertForbidden();
});

test('criação com cliente relacionado e sem cliente', function () {
    $commercial = commercialUser2();
    $client = Client::factory()->create();
    $this->actingAs($commercial)->post('/opportunities', [
        'title' => 'Com cliente',
        'client_id' => $client->id,
        'status' => 'em_negociacao',
    ])->assertRedirect();
    $this->assertDatabaseHas('opportunities', ['title' => 'Com cliente', 'client_id' => $client->id]);

    $this->actingAs($commercial)->post('/opportunities', [
        'title' => 'Sem cliente',
        'contact_name' => 'Contato X',
        'status' => 'nova',
    ])->assertRedirect();
    $this->assertDatabaseHas('opportunities', ['title' => 'Sem cliente']);
});

test('transições de status registram histórico', function () {
    $commercial = commercialUser2();
    $opp = Opportunity::factory()->create(['status' => 'nova']);

    $this->actingAs($commercial)->patch("/opportunities/{$opp->id}/status", ['status' => 'em_negociacao'])->assertRedirect();
    expect($opp->fresh()->status)->toBe('em_negociacao');
    $this->assertDatabaseHas('opportunity_activities', [
        'opportunity_id' => $opp->id,
        'action' => 'status_changed',
        'to_status' => 'em_negociacao',
    ]);
});

test('filtros por status e responsável', function () {
    $commercial = commercialUser2();
    $opp = Opportunity::factory()->create(['status' => 'ganha']);
    Opportunity::factory()->create(['status' => 'nova']);

    $this->actingAs($commercial)->get('/opportunities?status=ganha')->assertOk();
    $this->actingAs($commercial)->get("/opportunities?responsible_id={$opp->responsible_id}")->assertOk();
});

test('dashboard exibe indicadores reais', function () {
    $commercial = commercialUser2();
    Client::factory()->create(['status' => 'ativo']);
    Client::factory()->create(['status' => 'inativo']);
    Opportunity::factory()->create(['status' => 'nova', 'estimated_value' => 1000]);
    Opportunity::factory()->create(['status' => 'ganha', 'estimated_value' => 2000]);

    $response = $this->actingAs($commercial)->get('/dashboard');
    $response->assertRedirect('/commercial');
});
