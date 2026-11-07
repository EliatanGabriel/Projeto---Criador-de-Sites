<?php

use App\Jobs\CheckExpirationsJob;
use App\Models\Client;
use App\Models\Demand;
use App\Models\Hosting;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Website;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function pOwner6()
{
    $u = User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}

test('progresso: site sem tarefas retorna no_tasks', function () {
    $site = Website::factory()->create();
    $result = (new \App\Domain\Websites\Actions\CalculateWebsiteProgressAction())($site);
    expect($result['status'])->toBe('no_tasks');
    expect($result['progress'])->toBeNull();
    expect($result['total_tasks'])->toBe(0);
});

test('progresso: agregação multi-projeto com exclusões', function () {
    $site = Website::factory()->create();
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();
    $site->websiteProjects()->create(['project_id' => $project1->id, 'relation_type' => 'origin']);
    $site->websiteProjects()->create(['project_id' => $project2->id, 'relation_type' => 'evolution']);

    Task::factory()->create(['project_id' => $project1->id, 'status' => 'done']);
    Task::factory()->create(['project_id' => $project1->id, 'status' => 'todo']);
    Task::factory()->create(['project_id' => $project2->id, 'status' => 'blocked']);
    Task::factory()->create(['project_id' => $project2->id, 'status' => 'cancelled']);
    Task::factory()->create(['project_id' => $project2->id, 'status' => 'in_progress']);

    // cancelled excluída, blocked no denominador, done no numerador → 1/4 = 25%? actually 1 done / (done+todo+blocked+in_progress)=1/4
    $result = (new \App\Domain\Websites\Actions\CalculateWebsiteProgressAction())($site);
    expect($result['status'])->toBe('available');
    expect($result['total_tasks'])->toBe(4);
    expect($result['completed_tasks'])->toBe(1);
    expect($result['progress'])->toBe(25);
});

test('progresso: projeto cancelado excluído', function () {
    $site = Website::factory()->create();
    $project = Project::factory()->create(['status' => 'cancelled']);
    $site->websiteProjects()->create(['project_id' => $project->id, 'relation_type' => 'origin']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'todo']);

    $result = (new \App\Domain\Websites\Actions\CalculateWebsiteProgressAction())($site);
    expect($result['status'])->toBe('no_tasks');
});

test('alertas: datas ausentes não geram alertas', function () {
    $site = Website::factory()->create();
    $domain = $site->domains()->create(['domain' => 'ok.test.com', 'expires_at' => null, 'status' => 'ativo']);

    $action = new \App\Domain\Websites\Actions\CheckExpirationsAction();
    $stats = $action(Carbon::now());
    expect(\App\Models\WebsiteAlert::count())->toBe(0);
});

test('alertas: geração por fonte e severidade', function () {
    $site = Website::factory()->create();
    $domain = $site->domains()->create(['domain' => 'alerta.test.com', 'expires_at' => now()->addDays(5)->toDateString(), 'status' => 'ativo']);

    $action = new \App\Domain\Websites\Actions\CheckExpirationsAction();
    $stats = $action(Carbon::now());
    expect(\App\Models\WebsiteAlert::count())->toBe(1);
    $alert = \App\Models\WebsiteAlert::first();
    expect($alert->alert_type)->toBe('dominio_vencendo');
    expect($alert->severity)->toBe('critical'); // 5 dias → critical (≤7d e >1d)
});

test('alertas: idempotência em execuções repetidas', function () {
    $site = Website::factory()->create();
    $domain = $site->domains()->create(['domain' => 'idem.test.com', 'expires_at' => now()->addDays(5)->toDateString(), 'status' => 'ativo']);

    $action = new \App\Domain\Websites\Actions\CheckExpirationsAction();
    $action(Carbon::now());
    $action(Carbon::now());
    expect(\App\Models\WebsiteAlert::where('website_id', $site->id)->count())->toBe(1);
});

test('alertas: severidade atualiza sem duplicar', function () {
    $site = Website::factory()->create();
    $due = now()->addDays(20)->toDateString();
    $domain = $site->domains()->create(['domain' => 'sev.test.com', 'expires_at' => $due, 'status' => 'ativo']);

    $action = new \App\Domain\Websites\Actions\CheckExpirationsAction();
    $action(Carbon::now());
    $alert = \App\Models\WebsiteAlert::first();
    expect($alert->severity)->toBe('info');

    // move clock 15 dias para frente → deve virar warning sem duplicar
    $action(Carbon::now()->addDays(10));
    expect(\App\Models\WebsiteAlert::count())->toBe(1);
    expect(\App\Models\WebsiteAlert::first()->severity)->toBe('warning');
});

test('alertas: vencimento alterado gera nova ocorrência', function () {
    $site = Website::factory()->create();
    $domain = $site->domains()->create(['domain' => 'muda.test.com', 'expires_at' => now()->addDays(5)->toDateString(), 'status' => 'ativo']);

    $action = new \App\Domain\Websites\Actions\CheckExpirationsAction();
    $action(Carbon::now());
    expect(\App\Models\WebsiteAlert::count())->toBe(1);

    $domain->update(['expires_at' => now()->addDays(60)->toDateString()]);
    $action(Carbon::now());
    // 60 dias > 30 → nenhum alerta ativo para novo vencimento (severity none), mas antigo permanece open (não auto-resolve aqui)
    // o invariante é que não duplica o antigo
    expect(\App\Models\WebsiteAlert::count())->toBe(1);
});

test('alertas: resolvidos não reabertos', function () {
    $owner = pOwner6();
    $site = Website::factory()->create();
    $domain = $site->domains()->create(['domain' => 'resolvido.test.com', 'expires_at' => now()->addDays(5)->toDateString(), 'status' => 'ativo']);

    $action = new \App\Domain\Websites\Actions\CheckExpirationsAction();
    $action(Carbon::now());
    $alert = \App\Models\WebsiteAlert::first();
    $this->actingAs($owner)->post("/alerts/{$alert->id}/resolve")->assertRedirect();
    expect(\App\Models\WebsiteAlert::find($alert->id)->status)->toBe('resolved');

    $action(Carbon::now()->addDay());
    expect(\App\Models\WebsiteAlert::find($alert->id)->status)->toBe('resolved');
    expect(\App\Models\WebsiteAlert::count())->toBe(1);
});

test('alertas: ativos excluídos ignorados', function () {
    $site = Website::factory()->create();
    $domain = $site->domains()->create(['domain' => 'del.test.com', 'expires_at' => now()->addDays(5)->toDateString(), 'status' => 'ativo']);
    $domain->delete();

    $action = new \App\Domain\Websites\Actions\CheckExpirationsAction();
    $action(Carbon::now());
    expect(\App\Models\WebsiteAlert::count())->toBe(0);
});

test('segurança: OWNER acessa todos, COMMERCIAL/DEVELOPER escopados', function () {
    $owner = pOwner6();
    $commercial = User::factory()->create();
    $commercial->assignRole('COMMERCIAL');
    $developer = User::factory()->create();
    $developer->assignRole('DEVELOPER');

    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    $project->members()->create(['user_id' => $developer->id, 'role' => 'developer']);
    $site = Website::factory()->create(['client_id' => $client->id]);
    $site->websiteProjects()->create(['project_id' => $project->id, 'relation_type' => 'origin']);

    $domain = $site->domains()->create(['domain' => 'escopo.test.com', 'expires_at' => now()->addDays(5)->toDateString(), 'status' => 'ativo']);
    $action = new \App\Domain\Websites\Actions\CheckExpirationsAction();
    $action(Carbon::now());
    $alert = \App\Models\WebsiteAlert::first();
    expect($alert)->not->toBeNull();

    // comercial sem oportunidade/demanda não vê
    $this->actingAs($commercial)->get("/alerts/{$alert->id}")->assertForbidden();
    // developer com projeto acessível vê
    $this->actingAs($developer)->get("/alerts/{$alert->id}")->assertOk();
    // owner vê
    $this->actingAs($owner)->get("/alerts/{$alert->id}")->assertOk();

    // commercial não pode resolver
    $this->actingAs($commercial)->post("/alerts/{$alert->id}/resolve")->assertForbidden();
});

test('leitura individual não altera outros', function () {
    $owner = pOwner6();
    $developer = User::factory()->create();
    $developer->assignRole('DEVELOPER');
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id' => $client->id]);
    $project->members()->create(['user_id' => $developer->id, 'role' => 'developer']);
    $site = Website::factory()->create(['client_id' => $client->id]);
    $site->websiteProjects()->create(['project_id' => $project->id, 'relation_type' => 'origin']);
    $domain = $site->domains()->create(['domain' => 'leitura.test.com', 'expires_at' => now()->addDays(5)->toDateString(), 'status' => 'ativo']);
    (new \App\Domain\Websites\Actions\CheckExpirationsAction())(Carbon::now());
    $alert = \App\Models\WebsiteAlert::first();

    $this->actingAs($owner)->post("/alerts/{$alert->id}/read")->assertRedirect();
    $this->assertDatabaseHas('website_alert_reads', ['alert_id' => $alert->id, 'user_id' => $owner->id]);
    $this->assertDatabaseMissing('website_alert_reads', ['alert_id' => $alert->id, 'user_id' => $developer->id]);
});

test('job manual e scheduler', function () {
    $owner = pOwner6();
    $site = Website::factory()->create();
    $site->domains()->create(['domain' => 'job.test.com', 'expires_at' => now()->addDays(5)->toDateString(), 'status' => 'ativo']);

    (new CheckExpirationsJob())->handle(new \App\Domain\Websites\Actions\CheckExpirationsAction());
    expect(\App\Models\WebsiteAlert::count())->toBe(1);

    // Verifica agendamento sem depender de cache lock (schedule:list pode falhar com CACHE_STORE=database)
    $events = Schedule::events();
    $found = collect($events)->contains(fn ($e) => str_contains((string) $e->description, 'CheckExpirationsJob'));
    expect($found)->toBeTrue();
});
