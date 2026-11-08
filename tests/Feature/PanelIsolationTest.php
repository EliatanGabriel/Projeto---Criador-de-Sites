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

function isoUser(string $role): User
{
    $u = User::factory()->create();
    $u->assignRole($role);

    return $u;
}

test('painéis são rotas separadas com acesso por role', function () {
    $owner = isoUser('OWNER');
    $commercial = isoUser('COMMERCIAL');
    $developer = isoUser('DEVELOPER');

    $this->actingAs($owner)->get('/admin')->assertOk();
    $this->actingAs($owner)->get('/commercial')->assertOk();
    $this->actingAs($owner)->get('/developer')->assertOk();

    $this->actingAs($commercial)->get('/commercial')->assertOk();
    $this->actingAs($commercial)->get('/admin')->assertForbidden();
    $this->actingAs($commercial)->get('/developer')->assertForbidden();

    $this->actingAs($developer)->get('/developer')->assertOk();
    $this->actingAs($developer)->get('/admin')->assertForbidden();
    $this->actingAs($developer)->get('/commercial')->assertForbidden();
});

test('áreas owner-only são separadas de comercial e developer', function () {
    $commercial = isoUser('COMMERCIAL');
    $developer = isoUser('DEVELOPER');
    $owner = isoUser('OWNER');

    foreach (['/finance', '/team', '/settings', '/templates/create'] as $url) {
        $this->actingAs($commercial)->get($url)->assertForbidden("commercial não deve acessar {$url}");
        $this->actingAs($developer)->get($url)->assertForbidden("developer não deve acessar {$url}");
    }

    $this->actingAs($owner)->get('/finance')->assertOk();
    $this->actingAs($owner)->get('/team')->assertOk();
    $this->actingAs($owner)->get('/settings')->assertOk();
});

test('developer sem vínculo não vê projeto por id e vinculado vê', function () {
    $owner = isoUser('OWNER');
    $developer = isoUser('DEVELOPER');
    $otherDev = isoUser('DEVELOPER');
    $commercial = isoUser('COMMERCIAL');
    $client = Client::factory()->create();

    $linkedDemand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved', 'commercial_owner_id' => $commercial->id, 'created_by' => $commercial->id]);
    $assigned = Project::factory()->create(['demand_id' => $linkedDemand->id, 'client_id' => $client->id, 'technical_owner_id' => $developer->id]);
    $unassigned = Project::factory()->create(['demand_id' => Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']), 'client_id' => $client->id, 'technical_owner_id' => $otherDev->id]);

    $this->actingAs($developer)->get("/projects/{$assigned->id}")->assertOk()
        ->assertInertia(fn ($p) => $p->component('projects/show')->where('project.contracted_value', null)->where('canManage', false));
    $this->actingAs($developer)->get("/projects/{$unassigned->id}")->assertForbidden();
    $this->actingAs($owner)->get("/projects/{$unassigned->id}")->assertOk();
    $this->actingAs($commercial)->get("/projects/{$assigned->id}")->assertOk();
    $this->actingAs($commercial)->get("/projects/{$unassigned->id}")->assertForbidden();
});

test('developer não vê tarefa alheia por id, edição ou comentário', function () {
    $owner = isoUser('OWNER');
    $developer = isoUser('DEVELOPER');
    $other = isoUser('DEVELOPER');
    $own = Task::factory()->create(['assignee_id' => $developer->id]);
    $alheia = Task::factory()->create(['assignee_id' => $other->id]);

    $this->actingAs($developer)->get("/tasks/{$own->id}")->assertOk();
    $this->actingAs($developer)->get("/tasks/{$alheia->id}")->assertForbidden();
    $this->actingAs($developer)->get("/tasks/{$alheia->id}/edit")->assertForbidden();
    $this->actingAs($developer)->post("/tasks/{$alheia->id}/comments", ['comment' => 'x'])->assertForbidden();
    $this->actingAs($owner)->get("/tasks/{$alheia->id}")->assertOk();
});

test('developer só cria tarefa em projeto vinculado e para si', function () {
    $owner = isoUser('OWNER');
    $developer = isoUser('DEVELOPER');
    $other = isoUser('DEVELOPER');
    $client = Client::factory()->create();
    $assignedProject = Project::factory()->create(['client_id' => $client->id, 'technical_owner_id' => $developer->id]);
    $foreignProject = Project::factory()->create(['client_id' => $client->id, 'technical_owner_id' => $other->id]);

    $this->actingAs($developer)->post('/tasks', [
        'title' => 'Em projeto alheio', 'project_id' => $foreignProject->id,
        'assignee_id' => $developer->id, 'priority' => 'high', 'status' => 'todo',
    ])->assertForbidden();

    $this->actingAs($developer)->post('/tasks', [
        'title' => 'Para terceiro', 'project_id' => $assignedProject->id,
        'assignee_id' => $other->id, 'priority' => 'high', 'status' => 'todo',
    ])->assertForbidden();

    $this->actingAs($developer)->post('/tasks', [
        'title' => 'Minha tarefa', 'project_id' => $assignedProject->id,
        'assignee_id' => $developer->id, 'priority' => 'high', 'status' => 'todo',
    ])->assertRedirect();
    $this->assertDatabaseHas('tasks', ['title' => 'Minha tarefa', 'project_id' => $assignedProject->id]);

    $this->actingAs($owner)->post('/tasks', [
        'title' => 'Atribuída pelo owner', 'project_id' => $assignedProject->id,
        'assignee_id' => $developer->id, 'priority' => 'high', 'status' => 'todo',
    ])->assertRedirect();
});

test('comercial só edita demanda editável e developer não edita', function () {
    $commercial = isoUser('COMMERCIAL');
    $developer = isoUser('DEVELOPER');
    $client = Client::factory()->create();
    $draft = Demand::factory()->create(['client_id' => $client->id, 'status' => 'draft', 'commercial_owner_id' => $commercial->id, 'created_by' => $commercial->id]);
    $submitted = Demand::factory()->create(['client_id' => $client->id, 'status' => 'submitted', 'submitted_at' => now(), 'commercial_owner_id' => $commercial->id, 'created_by' => $commercial->id]);

    $this->actingAs($commercial)->get("/demands/{$draft->id}/edit")->assertOk();
    $this->actingAs($commercial)->get("/demands/{$submitted->id}/edit")->assertForbidden();
    $this->actingAs($commercial)->put("/demands/{$submitted->id}", [
        'title' => 'Tentativa', 'client_id' => $client->id, 'priority' => 'normal',
    ])->assertForbidden();
    $this->actingAs($developer)->get("/demands/{$draft->id}/edit")->assertForbidden();
    $this->actingAs($developer)->put("/demands/{$draft->id}", [
        'title' => 'Tentativa', 'client_id' => $client->id, 'priority' => 'normal',
    ])->assertForbidden();
});

test('developer vê demanda vinculada sem valor negociado nem nota interna', function () {
    $owner = isoUser('OWNER');
    $developer = isoUser('DEVELOPER');
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved', 'negotiated_value' => 12345.67, 'internal_notes' => 'nota interna']);
    Project::factory()->create(['demand_id' => $demand->id, 'client_id' => $client->id, 'technical_owner_id' => $developer->id]);

    $this->actingAs($developer)->get("/demands/{$demand->id}")->assertOk()
        ->assertInertia(fn ($p) => $p->component('demands/show')->where('demand.negotiated_value', null)->where('demand.internal_notes', null));
    $this->actingAs($owner)->get("/demands/{$demand->id}")->assertOk()
        ->assertInertia(fn ($p) => $p->component('demands/show')->where('demand.internal_notes', 'nota interna'));
});

test('dashboard genérico é separado: developer usa o painel developer', function () {
    $developer = isoUser('DEVELOPER');
    $commercial = isoUser('COMMERCIAL');
    $owner = isoUser('OWNER');

    $this->actingAs($developer)->get('/dashboard')->assertRedirect('/developer');
    $this->actingAs($commercial)->get('/dashboard')->assertOk()->assertInertia(fn ($p) => $p->component('dashboard'));
    $this->actingAs($owner)->get('/dashboard')->assertOk()->assertInertia(fn ($p) => $p->component('dashboard'));
});

test('painéis são conectados: comercial envia, owner aprova e converte, developer executa', function () {
    $owner = isoUser('OWNER');
    $commercial = isoUser('COMMERCIAL');
    $developer = isoUser('DEVELOPER');
    $client = Client::factory()->create();

    $response = $this->actingAs($commercial)->post('/demands', [
        'title' => 'Site conexão', 'client_id' => $client->id,
        'service_type' => 'Site institucional', 'description' => 'Desc',
        'priority' => 'alta', 'briefing' => ['objectives' => 'Vender'],
    ]);
    $response->assertRedirect();
    $demand = Demand::firstOrFail();
    expect($demand->commercial_owner_id)->toBe($commercial->id);

    $this->actingAs($commercial)->post("/demands/{$demand->id}/submit")->assertRedirect();
    expect($demand->fresh()->status)->toBe('submitted');
    expect($owner->fresh()->notifications()->count())->toBeGreaterThan(0);
    $this->actingAs($owner)->get('/admin')->assertOk();

    $this->actingAs($owner)->post("/demands/{$demand->id}/start-review")->assertRedirect();
    $this->actingAs($commercial)->post("/demands/{$demand->id}/approve", [])->assertForbidden();
    $this->actingAs($owner)->post("/demands/{$demand->id}/approve", ['notes' => 'OK'])->assertRedirect();
    $this->actingAs($owner)->post("/demands/{$demand->id}/convert")->assertRedirect();

    $project = Project::where('demand_id', $demand->id)->firstOrFail();
    $this->actingAs($developer)->get('/developer')->assertOk();
    $this->actingAs($developer)->get("/projects/{$project->id}")->assertForbidden();

    $project->update(['technical_owner_id' => $developer->id]);
    $project->members()->firstOrCreate(['user_id' => $developer->id], ['role' => 'technical']);
    $this->actingAs($developer)->get("/projects/{$project->id}")->assertOk();
    $this->actingAs($commercial)->get("/projects/{$project->id}")->assertOk();

    $task = Task::factory()->create(['project_id' => $project->id, 'assignee_id' => $developer->id]);
    $this->actingAs($developer)->put("/tasks/{$task->id}", [
        'title' => $task->title, 'priority' => 'medium', 'status' => 'blocked', 'blocked_reason' => 'Aguardando acesso',
    ])->assertRedirect();
    expect($owner->fresh()->notifications()->count())->toBeGreaterThan(0);
});
