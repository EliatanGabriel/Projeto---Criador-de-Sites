<?php

use App\Models\Client;
use App\Models\Demand;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function pOwner()
{
    $u = User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}
function pCommercial()
{
    $u = User::factory()->create();
    $u->assignRole('COMMERCIAL');
    return $u;
}
function pDeveloper()
{
    $u = User::factory()->create();
    $u->assignRole('DEVELOPER');
    return $u;
}
function approvedDemand($clientId = null)
{
    $client = Client::factory()->create();
    $demand = Demand::factory()->create([
        'client_id' => $clientId ?? $client->id,
        'status' => 'approved',
        'title' => 'Demanda aprovada',
        'negotiated_value' => 15000,
        'desired_deadline' => now()->addDays(45)->toDateString(),
    ]);
    return $demand;
}

test('demanda aprovada pode ser convertida por OWNER', function () {
    $owner = pOwner();
    $demand = approvedDemand();

    $response = $this->actingAs($owner)->post("/demands/{$demand->id}/convert");
    $response->assertRedirect();
    $this->assertDatabaseHas('projects', ['demand_id' => $demand->id, 'client_id' => $demand->client_id, 'status' => 'planning']);
    $project = Project::where('demand_id', $demand->id)->first();
    expect($project)->not->toBeNull();
    expect((float) $project->contracted_value)->toBeGreaterThan(0.0);
    $this->assertDatabaseHas('project_histories', ['project_id' => $project->id, 'event' => 'converted']);
    $this->assertDatabaseHas('demand_histories', ['demand_id' => $demand->id, 'event' => 'converted_to_project']);
    expect($owner->notifications()->count())->toBeGreaterThan(0);
});

test('demanda não aprovada não pode ser convertida', function () {
    $owner = pOwner();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'draft']);

    $this->actingAs($owner)->post("/demands/{$demand->id}/convert")->assertStatus(422);
    $this->assertDatabaseMissing('projects', ['demand_id' => $demand->id]);
});

test('usuário sem permissão não converte', function () {
    $commercial = pCommercial();
    $demand = approvedDemand();

    $this->actingAs($commercial)->post("/demands/{$demand->id}/convert")->assertForbidden();
});

test('uma demanda não gera projetos duplicados', function () {
    $owner = pOwner();
    $demand = approvedDemand();

    $this->actingAs($owner)->post("/demands/{$demand->id}/convert")->assertRedirect();
    $this->actingAs($owner)->post("/demands/{$demand->id}/convert")->assertStatus(422);
    expect(Project::where('demand_id', $demand->id)->count())->toBe(1);
});

test('concorrência não gera duplicidade (unique)', function () {
    $owner = pOwner();
    $demand = approvedDemand();
    Project::create([
        'demand_id' => $demand->id,
        'client_id' => $demand->client_id,
        'title' => 'Existente',
        'status' => 'planning',
        'priority' => 'medium',
    ]);

    $this->actingAs($owner)->post("/demands/{$demand->id}/convert")->assertStatus(422);
    expect(Project::where('demand_id', $demand->id)->count())->toBe(1);
});

test('falha na conversão não deixa dados parciais', function () {
    $owner = pOwner();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'draft']);

    $beforeProjects = Project::count();
    $beforeHistories = \App\Models\ProjectHistory::count();
    $this->actingAs($owner)->post("/demands/{$demand->id}/convert")->assertStatus(422);
    expect(Project::count())->toBe($beforeProjects);
    expect(\App\Models\ProjectHistory::count())->toBe($beforeHistories);
});
