<?php

use App\Models\Client;
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

function pOwner2()
{
    $u = User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}
function pDeveloper2()
{
    $u = User::factory()->create();
    $u->assignRole('DEVELOPER');
    return $u;
}
function pCommercial2()
{
    $u = User::factory()->create();
    $u->assignRole('COMMERCIAL');
    return $u;
}

test('cadastro e edição de projeto', function () {
    $owner = pOwner2();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);
    $project = Project::factory()->create(['demand_id' => $demand->id, 'client_id' => $client->id]);

    $this->actingAs($owner)->get('/projects')->assertOk();
    $this->actingAs($owner)->get("/projects/{$project->id}")->assertOk();

    $this->actingAs($owner)->put("/projects/{$project->id}", [
        'title' => 'Editado',
        'priority' => 'high',
    ])->assertRedirect();
    expect(Project::find($project->id)->title)->toBe('Editado');
});

test('alteração de status de projeto', function () {
    $owner = pOwner2();
    $project = Project::factory()->create(['status' => 'planning']);

    $this->actingAs($owner)->patch("/projects/{$project->id}/status", ['status' => 'in_progress'])->assertRedirect();
    expect(Project::find($project->id)->status)->toBe('in_progress');
});

test('bloqueio de projeto exige justificativa', function () {
    $owner = pOwner2();
    $project = Project::factory()->create(['status' => 'in_progress']);

    $this->actingAs($owner)->patch("/projects/{$project->id}/status", ['status' => 'blocked'])->assertSessionHasErrors();
    $this->actingAs($owner)->patch("/projects/{$project->id}/status", ['status' => 'blocked', 'blocked_reason' => 'Faltando material'])->assertRedirect();
    expect(Project::find($project->id)->status)->toBe('blocked');
});

test('conclusão de projeto', function () {
    $owner = pOwner2();
    $tech = pOwner2();
    $project = Project::factory()->create(['status' => 'in_progress', 'technical_owner_id' => $tech->id]);

    $this->actingAs($tech)->patch("/projects/{$project->id}/status", ['status' => 'completed'])->assertRedirect();
    expect(Project::find($project->id)->status)->toBe('completed');
    expect(Project::find($project->id)->completed_at)->not->toBeNull();
});

test('proteção por perfil em projetos', function () {
    $commercial = pCommercial2();
    $project = Project::factory()->create();

    $this->actingAs($commercial)->patch("/projects/{$project->id}/status", ['status' => 'completed'])->assertForbidden();
});

test('cadastro e atribuição de tarefas', function () {
    $owner = pOwner2();
    $dev = pDeveloper2();
    $project = Project::factory()->create();

    $response = $this->actingAs($owner)->post('/tasks', [
        'title' => 'Fazer layout',
        'project_id' => $project->id,
        'assignee_id' => $dev->id,
        'priority' => 'high',
        'status' => 'todo',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('tasks', ['title' => 'Fazer layout', 'assignee_id' => $dev->id]);
    $this->assertDatabaseHas('task_histories', ['event' => 'created']);
    expect($dev->fresh()->notifications()->count())->toBeGreaterThan(0);
});

test('atualização de status de tarefa pelo responsável', function () {
    $dev = pDeveloper2();
    $task = Task::factory()->create(['assignee_id' => $dev->id, 'status' => 'todo']);

    $this->actingAs($dev)->put("/tasks/{$task->id}", [
        'title' => $task->title,
        'priority' => 'medium',
        'status' => 'in_progress',
    ])->assertRedirect();
    expect(Task::find($task->id)->status)->toBe('in_progress');
});

test('bloqueio de tarefa exige justificativa', function () {
    $owner = pOwner2();
    $task = Task::factory()->create(['status' => 'todo']);

    $this->actingAs($owner)->put("/tasks/{$task->id}", [
        'title' => $task->title,
        'priority' => 'medium',
        'status' => 'blocked',
    ])->assertSessionHasErrors('blocked_reason');

    $this->actingAs($owner)->put("/tasks/{$task->id}", [
        'title' => $task->title,
        'priority' => 'medium',
        'status' => 'blocked',
        'blocked_reason' => 'Sem acesso',
    ])->assertRedirect();
    $this->assertDatabaseHas('task_histories', ['event' => 'status_changed', 'to_status' => 'blocked']);
});

test('conclusão de tarefa registra data e usuário', function () {
    $owner = pOwner2();
    $task = Task::factory()->create(['status' => 'in_progress']);

    $this->actingAs($owner)->put("/tasks/{$task->id}", [
        'title' => $task->title,
        'priority' => 'medium',
        'status' => 'done',
    ])->assertRedirect();
    $fresh = Task::find($task->id);
    expect($fresh->completed_at)->not->toBeNull();
    expect((string) $fresh->completed_by)->toBe((string) $owner->id);
});

test('developer não pode atribuir tarefas a terceiros nem atualizar tarefa alheia', function () {
    $dev = pDeveloper2();
    $other = pDeveloper2();
    $task = Task::factory()->create(['assignee_id' => $dev->id]);

    $this->actingAs($dev)->put("/tasks/{$task->id}", [
        'title' => $task->title,
        'priority' => 'medium',
        'status' => 'in_progress',
        'assignee_id' => $other->id,
    ])->assertForbidden();

    $otherTask = Task::factory()->create(['assignee_id' => $other->id]);
    $this->actingAs($dev)->put("/tasks/{$otherTask->id}", [
        'title' => $otherTask->title,
        'priority' => 'medium',
        'status' => 'done',
    ])->assertForbidden();
});

test('tarefas de projetos encerrados não podem ser alteradas', function () {
    $owner = pOwner2();
    $project = Project::factory()->create(['status' => 'completed']);
    $task = Task::factory()->create(['project_id' => $project->id, 'status' => 'todo']);

    $this->actingAs($owner)->put("/tasks/{$task->id}", [
        'title' => 'Nova',
        'priority' => 'high',
        'status' => 'in_progress',
    ])->assertSessionHasErrors();
});

test('notificações: geração, listagem e marcar como lida', function () {
    $owner = pOwner2();
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id, 'assignee_id' => $owner->id, 'status' => 'blocked', 'blocked_reason' => 'X']);
    $owner->notify(new \App\Notifications\TaskBlocked($task));

    $this->actingAs($owner)->get('/notifications')->assertOk();
    $n = $owner->notifications()->first();
    $this->actingAs($owner)->patch("/notifications/{$n->id}/read")->assertRedirect();
    expect($n->fresh()->read_at)->not->toBeNull();

    // outro usuário não pode marcar como lida
    $other = pOwner2();
    $this->actingAs($other)->patch("/notifications/{$n->id}/read")->assertForbidden();
});

test('comentário em tarefa', function () {
    $dev = pDeveloper2();
    $task = Task::factory()->create(['assignee_id' => $dev->id]);

    $this->actingAs($dev)->post("/tasks/{$task->id}/comments", ['comment' => 'Em andamento'])->assertRedirect();
    $this->assertDatabaseHas('task_comments', ['task_id' => $task->id, 'comment' => 'Em andamento']);
});
