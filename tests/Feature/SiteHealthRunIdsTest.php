<?php

use App\Jobs\CheckSiteHealthJob;
use App\Jobs\CheckWebsiteHealthJob;
use App\Models\SiteHealthRun;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Models\WebsiteDomainHealthState;
use App\Models\WebsiteEnvironment;
use App\Models\WebsiteHealthCheck;
use App\Domain\Websites\Actions\RunSiteHealthCheckAction;
use App\Domain\Websites\Services\HttpHealthChecker;
use App\Domain\Websites\Services\SslHealthChecker;
use App\Domain\Websites\Services\SsrfGuard;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('dois domínios do mesmo website geram dois históricos com IDs distintos', function () {
    $site = Website::factory()->create();
    $env = WebsiteEnvironment::factory()->create(['website_id' => $site->id, 'name' => 'Produção', 'type' => 'production', 'status' => 'ativo', 'url' => 'https://multi.example.com']);
    $domainA = WebsiteDomain::factory()->create(['website_id' => $site->id, 'domain' => 'multi.example.com', 'is_primary' => true, 'status' => 'ativo']);
    $domainB = WebsiteDomain::factory()->create(['website_id' => $site->id, 'domain' => 'www.multi.example.com', 'is_primary' => false, 'status' => 'ativo']);

    $slot = now('America/Sao_Paulo')->toDateString();
    Queue::fake();

    (new CheckSiteHealthJob())->allocateRuns($slot);

    Queue::assertPushed(CheckWebsiteHealthJob::class, 2);
    $pushed = Queue::pushedJobs()[CheckWebsiteHealthJob::class];
    $runIds = collect($pushed)->map(fn ($p) => $p['job']->scheduledRunId)->all();

    expect(count(array_unique($runIds)))->toBe(2);

    $action = new RunSiteHealthCheckAction(
        new SsrfGuard(fn () => [['ip' => '93.184.216.34']]),
        new HttpHealthChecker(fn () => ['status' => 200, 'headers' => [], 'error' => null, 'effective_ip' => '93.184.216.34', 'latency_ms' => 15]),
        new SslHealthChecker(fn () => ['expires' => Carbon\Carbon::now()->addYear()->toDateTimeString()]),
    );

    foreach ($pushed as $pushedJob) {
        $job = $pushedJob['job'];
        $job->handle($action);
    }

    $checks = WebsiteHealthCheck::where('website_id', $site->id)->orderBy('scheduled_run_id')->get();
    expect($checks)->toHaveCount(2);
    expect($checks[0]->scheduled_run_id)->toBe($checks[1]->scheduled_run_id - 1);
    expect(WebsiteHealthCheck::where('domain_id', $domainA->id)->exists())->toBeTrue();
    expect(WebsiteHealthCheck::where('domain_id', $domainB->id)->exists())->toBeTrue();
    expect(WebsiteDomainHealthState::where('website_id', $site->id)->count())->toBe(2);
    expect(SiteHealthRun::query()->where('scheduled_for', $slot)->count())->toBe(2);
});
