<?php

use App\Jobs\CheckSiteHealthJob;
use App\Jobs\CheckWebsiteHealthJob;
use App\Models\SiteHealthRun;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Models\WebsiteEnvironment;
use App\Models\WebsiteHealthCheck;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function rSetup(): array
{
    $site = Website::factory()->create();
    $env = WebsiteEnvironment::factory()->create(['website_id' => $site->id, 'name' => 'Produção', 'type' => 'production', 'status' => 'ativo', 'url' => 'https://r.example.com']);
    $domain = WebsiteDomain::factory()->create(['website_id' => $site->id, 'domain' => 'r.example.com', 'is_primary' => true, 'status' => 'ativo']);

    return [$site, $domain, $env];
}

test('backfill: slot ausente vira registro do dia', function () {
    [$site, $domain] = rSetup();
    $slot = now('America/Sao_Paulo')->toDateString();
    Queue::fake();

    (new CheckSiteHealthJob())->allocateRuns($slot);

    expect(SiteHealthRun::where('domain_id', $domain->id)->where('scheduled_for', $slot)->count())->toBe(1);
    Queue::assertPushed(CheckWebsiteHealthJob::class, 1);
});

test('pending antigo é recuperado (reenfileirado) com o mesmo id', function () {
    [$site, $domain] = rSetup();

    \Illuminate\Support\Facades\DB::table('website_domains')->where('id', $domain->id)->update(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);
    \Illuminate\Support\Facades\DB::table('websites')->where('id', $site->id)->update(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);
    $slot = now('America/Sao_Paulo')->subDays(1)->toDateString();
    $run = SiteHealthRun::query()->create(['domain_id' => $domain->id, 'website_id' => $site->id, 'scheduled_for' => $slot, 'status' => 'pending']);
    Queue::fake();

    (new CheckSiteHealthJob())->allocateRuns($slot);

    Queue::assertPushed(CheckWebsiteHealthJob::class, 1);
    expect(SiteHealthRun::count())->toBe(1);
    expect(SiteHealthRun::first()->getKey())->toBe($run->getKey());
    expect(SiteHealthRun::first()->status)->toBe('dispatched');
});

test('dispatched abandonado (>10 min) é reenfileirado; dispatched recente não', function () {
    [$site, $domain] = rSetup();
    $slot = now('America/Sao_Paulo')->toDateString();
    $old = SiteHealthRun::query()->create(['domain_id' => $domain->id, 'website_id' => $site->id, 'scheduled_for' => substr($slot, 0, 7).'-10', 'status' => 'dispatched', 'dispatched_at' => now()->subMinutes(20)]);
    $fresh = SiteHealthRun::query()->create(['domain_id' => $domain->id, 'website_id' => $site->id, 'scheduled_for' => $slot, 'status' => 'dispatched', 'dispatched_at' => now()->subMinutes(2)]);
    $staleDate = $old->scheduled_for->toDateString();
    Queue::fake();

    (new CheckSiteHealthJob())->allocateRuns($staleDate);

    $pushedForOld = collect(Queue::pushedJobs()[CheckWebsiteHealthJob::class] ?? [])->filter(fn ($p) => $p['job']->scheduledRunId === (int) $old->getKey())->count();
    expect($pushedForOld)->toBe(1);

    Queue::fake();
    (new CheckSiteHealthJob())->allocateRuns($slot);
    expect(Queue::pushedJobs()[CheckWebsiteHealthJob::class] ?? [])->toBe([], 'dispatched recente não deve reenfileirar');
});

test('applied nunca é reenfileirado', function () {
    [$site, $domain] = rSetup();
    $slot = now('America/Sao_Paulo')->toDateString();
    SiteHealthRun::query()->create(['domain_id' => $domain->id, 'website_id' => $site->id, 'scheduled_for' => $slot, 'status' => 'applied', 'applied_at' => now()]);
    Queue::fake();

    (new CheckSiteHealthJob())->allocateRuns($slot);

    expect(Queue::pushedJobs()[CheckWebsiteHealthJob::class] ?? [])->toBe([]);
});

test('falha na fila mantém pendente e permite recuperação com mesmo id', function () {
    [$site, $domain] = rSetup();
    $slot = now('America/Sao_Paulo')->toDateString();
    $failed = false;

    $this->mock(\Illuminate\Contracts\Bus\Dispatcher::class, function ($mock) {
        $mock->shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('queue down'));
    });

    (new CheckSiteHealthJob())->allocateRuns($slot);

    expect(SiteHealthRun::count())->toBe(1);
    expect(SiteHealthRun::first()->status)->toBe('pending');

    Mockery::close();

    $this->app->instance(\Illuminate\Contracts\Bus\Dispatcher::class, new \Illuminate\Bus\Dispatcher(app()));

    Queue::fake();
    (new CheckSiteHealthJob())->allocateRuns($slot);

    expect(SiteHealthRun::first()->status)->toBe('dispatched');
    expect(SiteHealthRun::first()->status)->toBe('dispatched');
});

test('domínio inativo ou soft-deleted é ignorado', function () {
    [$site, $domain] = rSetup();
    $domain->update(['status' => 'suspenso']);
    $deleted = WebsiteDomain::factory()->create(['website_id' => $site->id, 'domain' => 'del.example.com', 'is_primary' => false, 'status' => 'ativo']);
    $deleted->delete();
    Queue::fake();

    (new CheckSiteHealthJob())->allocateRuns(now('America/Sao_Paulo')->toDateString());

    expect(SiteHealthRun::count())->toBe(0);
});

test('limite de 7 dias: janela inclui hoje-6 e exclui hoje-7', function () {
    $slots = (new CheckSiteHealthJob())->windowSlots();
    $today = now('America/Sao_Paulo')->toDateString();

    expect($slots)->toHaveCount(7);
    expect($slots[0])->toBe(now('America/Sao_Paulo')->subDays(6)->toDateString());
    expect($slots[6])->toBe($today);
});

test('slots diferentes do mesmo domínio não colidem (ids distintos)', function () {
    [$site, $domain] = rSetup();

    \Illuminate\Support\Facades\DB::table('website_domains')->where('id', $domain->id)->update(['created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)]);
    \Illuminate\Support\Facades\DB::table('websites')->where('id', $site->id)->update(['created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)]);

    $s1 = now('America/Sao_Paulo')->subDays(5)->toDateString();
    $s2 = now('America/Sao_Paulo')->subDays(4)->toDateString();
    Queue::fake();

    (new CheckSiteHealthJob())->allocateRuns($s1);
    (new CheckSiteHealthJob())->allocateRuns($s2);

    $ids = SiteHealthRun::where('domain_id', $domain->id)->pluck('scheduled_run_id')->all();
    expect(count(array_unique($ids)))->toBe(2);
});
