<?php

use App\Jobs\CheckSiteHealthJob;
use App\Jobs\CheckWebsiteHealthJob;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Models\WebsiteDomainHealthState;
use App\Models\WebsiteEnvironment;
use App\Models\WebsiteHealthCheck;
use App\Domain\Websites\Actions\RunSiteHealthCheckAction;
use App\Domain\Websites\Services\HttpHealthChecker;
use App\Domain\Websites\Services\SslHealthChecker;
use App\Domain\Websites\Services\SsrfGuard;
use App\Models\SiteHealthRun;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function mSetup(): array
{
    $site = Website::factory()->create();
    $env = WebsiteEnvironment::factory()->create([
        'website_id' => $site->id,
        'name' => 'Produção',
        'type' => 'production',
        'status' => 'ativo',
        'url' => 'https://multi.example.com',
    ]);
    $domainA = WebsiteDomain::factory()->create(['website_id' => $site->id, 'domain' => 'multi.example.com', 'is_primary' => true, 'status' => 'ativo']);
    $domainB = WebsiteDomain::factory()->create(['website_id' => $site->id, 'domain' => 'www.multi.example.com', 'is_primary' => false, 'status' => 'ativo']);

    return [$site, $domainA, $domainB, $env];
}

function mOkAction(): RunSiteHealthCheckAction
{
    return new RunSiteHealthCheckAction(
        new SsrfGuard(fn () => [['ip' => '93.184.216.34']]),
        new HttpHealthChecker(fn () => ['status' => 200, 'headers' => [], 'error' => null, 'effective_ip' => '93.184.216.34', 'latency_ms' => 20]),
        new SslHealthChecker(fn () => ['expires' => Carbon\Carbon::now()->addYear()->toDateTimeString()]),
    );
}

test('coordenador despacha um Job por domínio com runIds distintos e reexecução recupera ids', function () {
    [$site, $domainA, $domainB] = mSetup();

    $domainA->update(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);
    $domainB->update(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);
    $site->update(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);

    Queue::fake();

    (new CheckSiteHealthJob())->allocateRuns('2026-11-07');
    Queue::assertPushed(CheckWebsiteHealthJob::class, 2);

    $pushed = Queue::pushedJobs()[CheckWebsiteHealthJob::class];
    $ids = collect($pushed)->map(fn ($p) => $p['job']->scheduledRunId)->sort()->values()->all();

    expect(count($ids))->toBe(2);
    expect($ids[0])->not->toBe($ids[1]);
    expect(SiteHealthRun::where('scheduled_for', '2026-11-07')->count())->toBe(2);

    SiteHealthRun::query()->update(['dispatched_at' => now()->subMinutes(20)]);

    Queue::fake();
    (new CheckSiteHealthJob())->allocateRuns('2026-11-07');

    $again = collect(Queue::pushedJobs()[CheckWebsiteHealthJob::class] ?? [])->map(fn ($p) => $p['job']->scheduledRunId)->sort()->values()->all();
    expect($again)->toBe($ids);
    expect(SiteHealthRun::count())->toBe(2);
});

test('execução agendada verifica os dois domínios e cria estados independentes', function () {
    [$site, $domainA, $domainB, $env] = mSetup();
    $action = mOkAction();
    $runA = (int) \Illuminate\Support\Facades\DB::selectOne("SELECT nextval('website_health_check_schedule_seq') AS r")->r;
    $runB = (int) \Illuminate\Support\Facades\DB::selectOne("SELECT nextval('website_health_check_schedule_seq') AS r")->r;

    $action->checkDomain($domainA, $site, $env, $runA, 'scheduled');
    $action->checkDomain($domainB, $site, $env, $runB, 'scheduled');

    expect(WebsiteHealthCheck::where('website_id', $site->id)->count())->toBe(2);
    expect(WebsiteHealthCheck::where('domain_id', $domainA->id)->count())->toBe(1);
    expect(WebsiteHealthCheck::where('domain_id', $domainB->id)->count())->toBe(1);
    expect(WebsiteDomainHealthState::where('domain_id', $domainA->id)->exists())->toBeTrue();
    expect(WebsiteDomainHealthState::where('domain_id', $domainB->id)->exists())->toBeTrue();
});

test('retry do Job individual reutiliza o runId sem duplicar histórico nem contador', function () {
    [$site, $domainA, $domainB, $env] = mSetup();
    $action = mOkAction();

    $run = SiteHealthRun::query()->create([
        'domain_id' => $domainA->id,
        'website_id' => $site->id,
        'scheduled_for' => '2026-11-07',
        'status' => 'dispatched',
    ]);

    $job = new CheckWebsiteHealthJob($site->id, $domainA->id, (int) $run->getKey());
    $job->handle($action);
    $job->handle($action);

    expect(WebsiteHealthCheck::where('scheduled_run_id', $run->getKey())->count())->toBe(1);
    expect(WebsiteDomainHealthState::where('domain_id', $domainA->id)->first()->last_applied_run_id)->toBe((int) $run->getKey());
    expect(WebsiteHealthCheck::where('domain_id', $domainB->id)->count())->toBe(0);
});

test('domínios inativos ou soft-deleted são ignorados pelo coordenador', function () {
    [$site, $domainA, $domainB] = mSetup();
    $domainB->update(['status' => 'suspenso']);
    $domainC = WebsiteDomain::factory()->create(['website_id' => $site->id, 'domain' => 'gone.example.com', 'is_primary' => false, 'status' => 'ativo']);
    $domainC->delete();

    Queue::fake();
    (new CheckSiteHealthJob())->allocateRuns('2026-11-07');

    Queue::assertPushed(CheckWebsiteHealthJob::class, 1);
    expect(SiteHealthRun::where('scheduled_for', '2026-11-07')->count())->toBe(1);
});

test('execuções concorrentes para o mesmo domínio são consistentes', function () {
    [$site, $domainA, $domainB, $env] = mSetup();
    $ok = mOkAction();
    $runOld = (int) \Illuminate\Support\Facades\DB::selectOne("SELECT nextval('website_health_check_schedule_seq') AS r")->r;
    $runNew = (int) \Illuminate\Support\Facades\DB::selectOne("SELECT nextval('website_health_check_schedule_seq') AS r")->r;

    $ok->checkDomain($domainA, $site, $env, $runNew, 'scheduled');
    $ok->checkDomain($domainA, $site, $env, $runOld, 'scheduled');

    expect(WebsiteDomainHealthState::where('domain_id', $domainA->id)->first()->last_applied_run_id)->toBe($runNew);
});
