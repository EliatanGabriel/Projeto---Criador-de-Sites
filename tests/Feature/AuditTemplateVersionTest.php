<?php

use App\Models\Client;
use App\Models\Demand;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aOwner()
{
    $u = User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}
function makeAudTemplate($ownerId)
{
    $t = ProjectTemplate::create([
        'name' => 'Template versão',
        'description' => 'Desc',
        'category' => 'Site institucional',
        'version' => '1.0',
        'is_active' => true,
        'created_by' => $ownerId,
    ]);
    $p = $t->phases()->create(['name' => 'Etapa 1', 'order' => 1]);
    $p->tasks()->create(['title' => 'Tarefa 1', 'priority' => 'high', 'suggested_role' => 'developer', 'estimated_days' => 3, 'order' => 1, 'is_required' => true]);
    return $t;
}

test('versão inicializada como 1.0 quando omitida', function () {
    $owner = aOwner();
    $this->actingAs($owner)->post('/templates', [
        'name' => 'Sem versão',
        'category' => 'Outros',
        'phases' => [['name' => 'E1', 'tasks' => [['title' => 'T1', 'priority' => 'medium']]]],
    ])->assertRedirect();
    $t = ProjectTemplate::where('name', 'Sem versão')->first();
    expect($t->version)->toBe('1.0');
});

test('edição sem versão incrementa automaticamente mínima', function () {
    $owner = aOwner();
    $template = makeAudTemplate($owner->id);
    expect($template->version)->toBe('1.0');

    $this->actingAs($owner)->put("/templates/{$template->id}", [
        'name' => 'Template versão editado',
        'category' => 'Site institucional',
        'phases' => [['name' => 'Nova etapa', 'tasks' => [['title' => 'Nova tarefa', 'priority' => 'medium']]]],
    ])->assertRedirect();

    expect(ProjectTemplate::find($template->id)->version)->toBe('1.1');
});

test('alterações em etapas e tarefas incrementam versão', function () {
    $owner = aOwner();
    $template = makeAudTemplate($owner->id);

    // só fases/tarefas (sem mudar nome) deve incrementar
    $this->actingAs($owner)->put("/templates/{$template->id}", [
        'name' => 'Template versão',
        'category' => 'Site institucional',
        'version' => '1.0',
        'phases' => [['name' => 'Etapa alterada', 'tasks' => [['title' => 'Tarefa alterada', 'priority' => 'urgent']]]],
    ])->assertRedirect();

    $fresh = ProjectTemplate::find($template->id);
    // se versão explicitamente igual, deve incrementar para 1.1 (não aceitar regressão/estagnação)
    expect($fresh->version)->toBe('1.1');
    expect($fresh->phases->first()->name)->toBe('Etapa alterada');
});

test('versão manual maior é respeitada', function () {
    $owner = aOwner();
    $template = makeAudTemplate($owner->id);

    $this->actingAs($owner)->put("/templates/{$template->id}", [
        'name' => 'Template versão',
        'category' => 'Site institucional',
        'version' => '2.0',
        'phases' => [['name' => 'Etapa 1', 'tasks' => [['title' => 'T1', 'priority' => 'high']]]],
    ])->assertRedirect();

    expect(ProjectTemplate::find($template->id)->version)->toBe('2.0');
});

test('conversão registra versão exata e snapshot integral', function () {
    $owner = aOwner();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);
    $template = makeAudTemplate($owner->id);
    $template->update(['version' => '1.5']);

    $this->actingAs($owner)->post('/projects', [
        'demand_id' => $demand->id,
        'template_id' => $template->id,
    ])->assertRedirect();

    $project = Project::where('demand_id', $demand->id)->first();
    expect($project->template_version)->toBe('1.5');
    expect($project->template_snapshot['version'])->toBe('1.5');
    expect($project->template_snapshot['phases'][0]['tasks'][0]['title'])->toBe('Tarefa 1');
});

test('assignments inválidas são rejeitadas', function () {
    $owner = aOwner();
    $inactive = User::factory()->create(['is_active' => false]);
    $inactive->assignRole('DEVELOPER');
    $commercial = User::factory()->create(['is_active' => true]);
    $commercial->assignRole('COMMERCIAL');
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);
    $template = makeAudTemplate($owner->id);
    $taskId = $template->phases->first()->tasks->first()->id;

    // inexistente
    $this->actingAs($owner)->post('/projects', [
        'demand_id' => $demand->id,
        'template_id' => $template->id,
        'assignments' => [$taskId => '00000000-0000-0000-0000-000000000000'],
    ])->assertSessionHasErrors();

    // inativo
    $this->actingAs($owner)->post('/projects', [
        'demand_id' => $demand->id,
        'template_id' => $template->id,
        'assignments' => [$taskId => $inactive->id],
    ])->assertSessionHasErrors();

    // perfil incompatível (COMMERCIAL)
    $this->actingAs($owner)->post('/projects', [
        'demand_id' => $demand->id,
        'template_id' => $template->id,
        'assignments' => [$taskId => $commercial->id],
    ])->assertSessionHasErrors();

    // sucesso com DEVELOPER ativo
    $dev = User::factory()->create();
    $dev->assignRole('DEVELOPER');
    $this->actingAs($owner)->post('/projects', [
        'demand_id' => $demand->id,
        'template_id' => $template->id,
        'assignments' => [$taskId => $dev->id],
    ])->assertRedirect();
    $project = Project::where('demand_id', $demand->id)->first();
    expect($project->tasks()->where('assignee_id', $dev->id)->count())->toBe(1);
});
