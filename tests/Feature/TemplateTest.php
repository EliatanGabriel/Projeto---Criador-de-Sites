<?php

use App\Models\Client;
use App\Models\Demand;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function tOwner()
{
    $u = User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}
function tCommercial()
{
    $u = User::factory()->create();
    $u->assignRole('COMMERCIAL');
    return $u;
}
function tDeveloper()
{
    $u = User::factory()->create();
    $u->assignRole('DEVELOPER');
    return $u;
}
function makeTemplate($ownerId = null)
{
    $template = ProjectTemplate::create([
        'name' => 'Site institucional',
        'description' => 'Padrão',
        'category' => 'Site institucional',
        'version' => '1.0',
        'is_active' => true,
        'created_by' => $ownerId,
    ]);
    $p1 = $template->phases()->create(['name' => 'Planejamento', 'order' => 1]);
    $p1->tasks()->create(['title' => 'Analisar briefing', 'priority' => 'high', 'suggested_role' => 'owner', 'estimated_days' => 2, 'order' => 1, 'is_required' => true]);
    $p1->tasks()->create(['title' => 'Organizar materiais', 'priority' => 'medium', 'suggested_role' => 'owner', 'estimated_days' => 2, 'order' => 2, 'is_required' => false]);
    $p2 = $template->phases()->create(['name' => 'Desenvolvimento', 'order' => 2]);
    $p2->tasks()->create(['title' => 'Desenvolver páginas', 'priority' => 'high', 'suggested_role' => 'developer', 'estimated_days' => 10, 'order' => 1, 'is_required' => true]);
    return $template;
}

test('CRUD de templates pelo OWNER', function () {
    $owner = tOwner();
    $this->actingAs($owner)->get('/templates')->assertOk();

    $response = $this->actingAs($owner)->post('/templates', [
        'name' => 'Novo template',
        'category' => 'Landing page',
        'version' => '1.0',
        'phases' => [
            ['name' => 'Etapa 1', 'tasks' => [['title' => 'Tarefa 1', 'priority' => 'high', 'suggested_role' => 'developer', 'estimated_days' => 3]]],
        ],
    ]);
    $response->assertRedirect();
    $t = ProjectTemplate::where('name', 'Novo template')->first();
    expect($t)->not->toBeNull();
    expect($t->phases->count())->toBe(1);
    expect($t->phases->first()->tasks->count())->toBe(1);

    $this->actingAs($owner)->get("/templates/{$t->id}")->assertOk();
    $this->actingAs($owner)->put("/templates/{$t->id}", [
        'name' => 'Editado',
        'category' => 'Landing page',
        'version' => '1.1',
        'phases' => [['name' => 'Nova etapa', 'tasks' => [['title' => 'Nova tarefa', 'priority' => 'medium']]]],
    ])->assertRedirect();
    expect(ProjectTemplate::find($t->id)->name)->toBe('Editado');
    expect(ProjectTemplate::find($t->id)->phases->count())->toBe(1);
});

test('permissões de administração: commercial e developer não administram', function () {
    $commercial = tCommercial();
    $developer = tDeveloper();
    $this->actingAs($commercial)->get('/templates/create')->assertForbidden();
    $this->actingAs($developer)->get('/templates/create')->assertForbidden();
    $this->actingAs($commercial)->post('/templates', ['name' => 'X', 'category' => 'Outros'])->assertForbidden();
});

test('ativação e inativação', function () {
    $owner = tOwner();
    $template = makeTemplate($owner->id);
    $this->actingAs($owner)->post("/templates/{$template->id}/toggle-active")->assertRedirect();
    expect($template->fresh()->is_active)->toBeFalse();
    $this->actingAs($owner)->post("/templates/{$template->id}/toggle-active")->assertRedirect();
    expect($template->fresh()->is_active)->toBeTrue();
});

test('templates inativos não podem ser selecionados na conversão', function () {
    $owner = tOwner();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);
    $template = makeTemplate($owner->id);
    $template->update(['is_active' => false]);

    // Hoje retorna 422 via validação FormRequest (session errors) ou 302 com erro – ambos indicam bloqueio
    $response = $this->actingAs($owner)->post('/projects', [
        'demand_id' => $demand->id,
        'template_id' => $template->id,
    ]);
    $project = Project::where('demand_id', $demand->id)->first();
    expect($project)->toBeNull();
    $response->assertSessionHasErrors();
});

test('seleção de template na conversão gera tarefas', function () {
    $owner = tOwner();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved', 'title' => 'Demanda X']);
    $template = makeTemplate($owner->id);

    $response = $this->actingAs($owner)->post('/projects', [
        'demand_id' => $demand->id,
        'template_id' => $template->id,
    ]);
    $response->assertRedirect();
    $project = Project::where('demand_id', $demand->id)->first();
    expect($project)->not->toBeNull();
    expect($project->tasks()->count())->toBe(3);
    expect($project->template_id)->toBe($template->id);
    expect($project->template_version)->toBe('1.0');
    expect($project->tasks()->where('phase_name', 'Planejamento')->count())->toBe(2);
});

test('preservação do responsável sugerido e tarefas sem responsável', function () {
    $owner = tOwner();
    $developer = tDeveloper();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);
    $template = makeTemplate($owner->id);

    $this->actingAs($owner)->post('/projects', [
        'demand_id' => $demand->id,
        'template_id' => $template->id,
    ])->assertRedirect();

    // tarefas sem assignments devem ficar sem responsável
    $project = Project::where('demand_id', $demand->id)->first();
    expect($project->tasks()->whereNull('assignee_id')->count())->toBe(3);
});

test('ajuste de responsáveis e exclusão de opcionais na conversão', function () {
    $owner = tOwner();
    $developer = tDeveloper();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);
    $template = makeTemplate($owner->id);
    $optionalId = $template->phases->first()->tasks->firstWhere('is_required', false)->id;

    $this->actingAs($owner)->post('/projects', [
        'demand_id' => $demand->id,
        'template_id' => $template->id,
        'assignments' => [$template->phases->last()->tasks->first()->id => $developer->id],
        'excluded_task_ids' => [$optionalId],
    ])->assertRedirect();

    $project = Project::where('demand_id', $demand->id)->first();
    expect($project->tasks()->count())->toBe(2);
    expect($project->tasks()->where('assignee_id', $developer->id)->count())->toBe(1);
});

test('geração sem template', function () {
    $owner = tOwner();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);

    $this->actingAs($owner)->post('/projects', ['demand_id' => $demand->id])->assertRedirect();
    $project = Project::where('demand_id', $demand->id)->first();
    expect($project->tasks()->count())->toBe(0);
    expect($project->template_id)->toBeNull();
});

test('rollback em falha de geração', function () {
    $owner = tOwner();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'draft']);
    $template = makeTemplate($owner->id);

    $beforeProjects = Project::count();
    $beforeTasks = Task::count();
    $this->actingAs($owner)->post('/projects', [
        'demand_id' => $demand->id,
        'template_id' => $template->id,
    ])->assertStatus(422);
    expect(Project::count())->toBe($beforeProjects);
    expect(Task::count())->toBe($beforeTasks);
});

test('snapshot preserva estado original', function () {
    $owner = tOwner();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);
    $template = makeTemplate($owner->id);

    $this->actingAs($owner)->post('/projects', ['demand_id' => $demand->id, 'template_id' => $template->id])->assertRedirect();
    $project = Project::where('demand_id', $demand->id)->first();
    expect($project->template_snapshot)->not->toBeNull();
    expect($project->template_snapshot['phases'][0]['name'])->toBe('Planejamento');

    // altera template depois – projeto não muda
    $template->update(['name' => 'Alterado']);
    $template->phases()->first()->update(['name' => 'Etapa alterada']);
    expect(Project::find($project->id)->tasks()->where('phase_name', 'Planejamento')->count())->toBe(2);
    expect(Project::find($project->id)->template_snapshot['phases'][0]['name'])->toBe('Planejamento');
});

test('visualização de etapas no projeto agrupa tarefas', function () {
    $owner = tOwner();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);
    $template = makeTemplate($owner->id);

    $this->actingAs($owner)->post('/projects', ['demand_id' => $demand->id, 'template_id' => $template->id])->assertRedirect();
    $project = Project::where('demand_id', $demand->id)->first();

    $response = $this->actingAs($owner)->get("/projects/{$project->id}");
    $response->assertOk();
    $props = $response->viewData('page')['props'] ?? [];
    // garante que tarefas têm phase_name preservado
    expect($project->tasks()->first()->phase_name)->not->toBeNull();
});

test('proteção por perfil na listagem de templates', function () {
    $developer = tDeveloper();
    // developer tem templates.view, pode listar mas não criar/editar
    $this->actingAs($developer)->get('/templates')->assertOk();
    $this->actingAs($developer)->post('/templates', ['name' => 'X', 'category' => 'Outros'])->assertForbidden();
});
