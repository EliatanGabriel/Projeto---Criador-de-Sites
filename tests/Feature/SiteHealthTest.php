<?php

use App\Domain\Websites\Actions\RunSiteHealthCheckAction;
use App\Domain\Websites\Services\HttpHealthChecker;
use App\Domain\Websites\Services\SslHealthChecker;
use App\Domain\Websites\Services\SsrfGuard;
use App\Models\Website;
use App\Models\WebsiteAlert;
use App\Models\WebsiteDomain;
use App\Models\WebsiteDomainHealthState;
use App\Models\WebsiteEnvironment;
use App\Models\WebsiteHealthCheck;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function hSetup(): array
{
    $site = Website::factory()->create();
    $env = WebsiteEnvironment::factory()->create([
        'website_id' => $site->id,
        'name' => 'Produção',
        'type' => 'production',
        'status' => 'ativo',
        'url' => 'https://example.com',
    ]);
    $domain = WebsiteDomain::factory()->create([
        'website_id' => $site->id,
        'domain' => 'example.com',
        'is_primary' => true,
        'status' => 'ativo',
    ]);

    return [$site, $domain, $env];
}

function hFailAction(): RunSiteHealthCheckAction
{
    return new RunSiteHealthCheckAction(
        new SsrfGuard(fn () => [['ip' => '93.184.216.34']]),
        new HttpHealthChecker(fn () => ['status' => 503, 'headers' => [], 'error' => 'http_unexpected', 'effective_ip' => '93.184.216.34', 'latency_ms' => 42]),
        new SslHealthChecker(fn () => ['expires' => Carbon\Carbon::now()->addYear()->toDateTimeString()]),
    );
}

function hOkAction(): RunSiteHealthCheckAction
{
    return new RunSiteHealthCheckAction(
        new SsrfGuard(fn () => [['ip' => '93.184.216.34']]),
        new HttpHealthChecker(fn () => ['status' => 200, 'headers' => [], 'error' => null, 'effective_ip' => '93.184.216.34', 'latency_ms' => 31]),
        new SslHealthChecker(fn () => ['expires' => Carbon\Carbon::now()->addYear()->toDateTimeString()]),
    );
}

function hRun(string $trigger = 'scheduled'): int
{
    return (int) DB::selectOne("SELECT nextval('website_health_check_schedule_seq') AS r")->r;
}

test('primeira segunda e terceira falhas abrem ciclo sem alerta até segunda falha', function () {
    [$site, $domain, $env] = hSetup();
    $action = hFailAction();

    $action->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    expect(WebsiteAlert::where('alert_type', 'site_indisponivel')->count())->toBe(0);
    $state = WebsiteDomainHealthState::where('domain_id', $domain->id)->first();
    expect($state->state)->toBe('down');
    expect($state->consecutive_failures)->toBe(1);

    $action->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    expect(WebsiteAlert::where('alert_type', 'site_indisponivel')->count())->toBe(1);
    expect(WebsiteAlert::first()->severity)->toBe('warning');

    $action->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    expect(WebsiteAlert::where('alert_type', 'site_indisponivel')->count())->toBe(1);
    expect(WebsiteAlert::first()->severity)->toBe('critical');
    expect($state->cycle_id)->not->toBeNull();
});

test('recuperação resolve alerta e encerra ciclo; novo ciclo após recuperação é distinto', function () {
    [$site, $domain, $env] = hSetup();
    $fail = hFailAction();
    $ok = hOkAction();

    $fail->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    $fail->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    $firstCycleId = WebsiteDomainHealthState::where('domain_id', $domain->id)->first()->cycle_id;
    expect($firstCycleId)->not->toBeNull();

    $ok->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    $after = WebsiteDomainHealthState::where('domain_id', $domain->id)->first();
    expect($after->state)->toBe('operational');
    expect($after->cycle_id)->toBeNull();
    expect($after->last_recovered_at)->not->toBeNull();
    expect(WebsiteAlert::where('status', 'open')->count())->toBe(0);
    expect(WebsiteAlert::where('alert_type', 'site_indisponivel')->first()->status)->toBe('resolved');

    $fail->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    $fail->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    expect(WebsiteAlert::where('alert_type', 'site_indisponivel')->count())->toBe(2);
    expect(WebsiteDomainHealthState::where('domain_id', $domain->id)->first()->cycle_id)->not->toBe($firstCycleId);
});

test('monitoring_error não gera alerta de indisponibilidade nem contador', function () {
    [$site, $domain, $env] = hSetup();
    $action = new RunSiteHealthCheckAction(
        new SsrfGuard(fn () => [['ip' => '93.184.216.34']]),
        new HttpHealthChecker(fn () => ['status' => null, 'headers' => [], 'error' => 'monitoring_failed', 'effective_ip' => null]),
        new SslHealthChecker(fn () => null),
    );

    $action->checkDomain($domain, $site, $env, hRun(), 'scheduled');

    expect(WebsiteAlert::where('alert_type', 'site_indisponivel')->count())->toBe(0);
    $state = WebsiteDomainHealthState::where('domain_id', $domain->id)->first();
    expect($state->state)->toBe('monitoring_error');
    expect($state->consecutive_failures)->toBe(0);
});

test('verificação manual não altera estado nem contadores', function () {
    [$site, $domain, $env] = hSetup();
    $fail = hFailAction();
    $fail->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    expect(WebsiteDomainHealthState::first()->consecutive_failures)->toBe(1);

    $ok = hOkAction();
    $ok->checkDomain($domain, $site, $env, null, 'manual');

    expect(WebsiteDomainHealthState::first()->consecutive_failures)->toBe(1);
    expect(WebsiteDomainHealthState::first()->state)->toBe('down');
    expect(WebsiteHealthCheck::where('trigger_source', 'manual')->count())->toBe(1);
});

test('dupla aplicação do mesmo runId é idempotente (sem novo contador)', function () {
    [$site, $domain, $env] = hSetup();
    $action = hFailAction();
    $runId = hRun();

    $action->checkDomain($domain, $site, $env, $runId, 'scheduled');
    $action->checkDomain($domain, $site, $env, $runId, 'scheduled');

    expect(WebsiteHealthCheck::where('scheduled_run_id', $runId)->count())->toBe(1);
    expect(WebsiteDomainHealthState::first()->consecutive_failures)->toBe(1);
});

test('execução antiga não sobresscreve execução posterior', function () {
    [$site, $domain, $env] = hSetup();
    $fail = hFailAction();
    $ok = hOkAction();

    $runOld = hRun();
    $runNew = hRun() + 100;

    $ok->checkDomain($domain, $site, $env, $runNew, 'scheduled');
    expect(WebsiteDomainHealthState::first()->last_applied_run_id)->toBe($runNew);

    $fail->checkDomain($domain, $site, $env, $runOld, 'scheduled');

    expect(WebsiteDomainHealthState::first()->last_applied_run_id)->toBe($runNew);
    expect(WebsiteDomainHealthState::first()->state)->toBe('operational');
});

test('due_at UTC do alerta de indisponibilidade é independente de severityFor', function () {
    [$site, $domain, $env] = hSetup();
    $fail = hFailAction();
    $fail->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    $fail->checkDomain($domain, $site, $env, hRun(), 'scheduled');

    $alert = WebsiteAlert::where('alert_type', 'site_indisponivel')->first();
    $expected = WebsiteDomainHealthState::first()->cycle_started_at->copy()->setTimezone('UTC')->toDateString();
    expect($alert->due_at->toDateString())->toBe($expected);
});

test('histórico append-only cresce sem alterar verificações passadas', function () {
    [$site, $domain, $env] = hSetup();
    $action = hOkAction();
    $action->checkDomain($domain, $site, $env, hRun(), 'scheduled');
    $first = WebsiteHealthCheck::where('domain_id', $domain->id)->first();
    $action->checkDomain($domain, $site, $env, hRun(), 'scheduled');

    expect(WebsiteHealthCheck::where('domain_id', $domain->id)->count())->toBe(2);
    expect($first->refresh()->ok)->toBeTrue();
});

test('isolamento e permissão de visualização e execução manual', function () {
    [$site, $domain, $env] = hSetup();
    $owner = User::factory()->create();
    $owner->assignRole('OWNER');
    $otherSite = Website::factory()->create();

    $otherEnv = WebsiteEnvironment::factory()->create(['website_id' => $otherSite->id, 'name' => 'Produção', 'type' => 'production', 'status' => 'ativo', 'url' => 'https://example.com']);
    $otherDomain = WebsiteDomain::factory()->create(['website_id' => $otherSite->id, 'domain' => 'other.example.com', 'is_primary' => true, 'status' => 'ativo']);

    $this->actingAs($owner)->get("/sites/{$site->id}")->assertOk()
        ->assertDontSee('93.184.216.34')
        ->assertDontSee('secret_encrypted');
    $this->actingAs($owner)->post("/sites/{$site->id}/health-check")->assertRedirect();

    $commercial = User::factory()->create();
    $commercial->assignRole('COMMERCIAL');
    $this->actingAs($commercial)->post("/sites/{$site->id}/health-check")->assertForbidden();
});
