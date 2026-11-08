<?php

use App\Models\Demand;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function developerUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('DEVELOPER');

    return $user;
}

function ownerForDev(): User
{
    $user = User::factory()->create();
    $user->assignRole('OWNER');

    return $user;
}

test('developer acessa painel developer e login developer', function () {
    $developer = developerUser();

    $this->get('/login/developer')->assertOk()->assertInertia(function ($page) {
        $page->component('auth/login')->where('panel', 'developer');
    });
    $this->actingAs($developer)->get('/developer')->assertOk()->assertInertia(fn ($page) => $page->component('developer/dashboard'));
    $this->actingAs($developer)->get('/login/developer')->assertRedirect('/developer');
});

test('developer tem permissoes de execucao completas', function () {
    $developer = developerUser();

    expect($developer->can('demands.view'))->toBeTrue()
        ->and($developer->can('tasks.create'))->toBeTrue()
        ->and($developer->can('projects.edit'))->toBeFalse()
        ->and($developer->can('demands.approve'))->toBeFalse()
        ->and($developer->can('finance.view'))->toBeFalse();
});

test('developer ve briefing da demanda do seu projeto mas nao de demanda alheia', function () {
    $developer = developerUser();
    $client = \App\Models\Client::factory()->create();
    $linked = Demand::create([
        'title' => 'Demanda vinculada', 'client_id' => $client->id,
        'priority' => 'normal', 'status' => 'approved', 'created_by' => $developer->id,
    ]);
    $project = Project::create([
        'title' => 'Projeto dev', 'client_id' => $client->id, 'demand_id' => $linked->id,
        'technical_owner_id' => $developer->id, 'priority' => 'high', 'status' => 'in_progress',
    ]);
    $unlinked = Demand::create([
        'title' => 'Demanda alheia', 'client_id' => $client->id,
        'priority' => 'normal', 'status' => 'approved', 'created_by' => ownerForDev()->id,
    ]);

    $this->actingAs($developer)->get("/demands/{$linked->id}")->assertOk();
    $this->actingAs($developer)->get("/demands/{$unlinked->id}")->assertForbidden();
    $this->actingAs($developer)->get('/demands')->assertOk()->assertInertia(function ($page) use ($linked) {
        $page->component('demands/index');
    });
});

test('developer atualiza status e bloqueio do projeto atribuido sem editar valor', function () {
    $developer = developerUser();
    $client = \App\Models\Client::factory()->create();
    $demandX = Demand::create(['title' => 'D status', 'client_id' => $client->id, 'priority' => 'normal', 'status' => 'approved', 'created_by' => $developer->id]);
    $project = Project::create([
        'title' => 'Projeto status', 'client_id' => $client->id, 'demand_id' => $demandX->id,
        'technical_owner_id' => $developer->id, 'priority' => 'high', 'status' => 'planning',
        'contracted_value' => 10000,
    ]);

    $this->actingAs($developer)->patch("/projects/{$project->id}/status", [
        'status' => 'blocked', 'blocked_reason' => 'Aguardando acesso',
    ])->assertRedirect();
    expect($project->fresh()->status)->toBe('blocked')
        ->and($project->fresh()->blocked_reason)->toBe('Aguardando acesso');

    $this->actingAs($developer)->get("/projects/{$project->id}")->assertOk()->assertInertia(function ($page) {
        $page->where('project.contracted_value', null)->where('canUpdateStatus', true)->where('canManage', false);
    });

    $this->actingAs($developer)->put("/projects/{$project->id}", [
        'title' => 'X', 'priority' => 'high', 'contracted_value' => 1,
    ])->assertForbidden();
});

test('developer nao acessa painéis admin nem comercial', function () {
    $developer = developerUser();

    $this->actingAs($developer)->get('/admin')->assertForbidden();
    $this->actingAs($developer)->get('/commercial')->assertForbidden();
});

test('developer cria tarefa em projeto acessivel', function () {
    $developer = developerUser();
    $client = \App\Models\Client::factory()->create();
    $demandY = Demand::create(['title' => 'D tarefas', 'client_id' => $client->id, 'priority' => 'normal', 'status' => 'approved', 'created_by' => $developer->id]);
    $project = Project::create([
        'title' => 'Projeto tarefas', 'client_id' => $client->id, 'demand_id' => $demandY->id,
        'technical_owner_id' => $developer->id, 'priority' => 'high', 'status' => 'in_progress',
    ]);

    $this->actingAs($developer)->post('/tasks', [
        'title' => 'Subtarefa do dev', 'project_id' => $project->id,
        'assignee_id' => $developer->id, 'priority' => 'high', 'status' => 'todo',
    ])->assertRedirect();
    $this->assertDatabaseHas('tasks', ['title' => 'Subtarefa do dev', 'project_id' => $project->id]);
});
