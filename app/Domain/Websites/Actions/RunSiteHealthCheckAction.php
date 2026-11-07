<?php

namespace App\Domain\Websites\Actions;

use App\Domain\Websites\Services\HttpHealthChecker;
use App\Domain\Websites\Services\SslHealthChecker;
use App\Domain\Websites\Services\SsrfGuard;
use App\Models\Website;
use App\Models\WebsiteAlert;
use App\Models\WebsiteDomain;
use App\Models\WebsiteDomainHealthState;
use App\Models\WebsiteHealthCheck;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RunSiteHealthCheckAction
{
    public function __construct(
        private readonly SsrfGuard $guard = new SsrfGuard(),
        private readonly HttpHealthChecker $http = new HttpHealthChecker(),
        private readonly SslHealthChecker $ssl = new SslHealthChecker(),
    ) {}

    public function checkDomain(WebsiteDomain $domain, Website $website, ?\App\Models\WebsiteEnvironment $environment, ?int $runId, string $trigger = 'scheduled'): ?WebsiteHealthCheck
    {
        $url = $this->buildUrl($domain);
        $validated = null;
        $httpResult = ['http_status' => null, 'ok' => false, 'latency_ms' => null, 'error_code' => 'dns_failed', 'effective_ip' => null];
        $sslResult = ['ssl_valid' => null, 'ssl_expires_at' => null, 'error_code' => null];
        $checkedAt = now();

        try {
            if (! $url) {
                $httpResult['error_code'] = 'dns_failed';
            } else {
                $validated = $this->guard->parseUrl($url);
                $httpResult = $this->http->check($url, $this->guard, $validated);
                $sslResult = $this->ssl->check($validated['host'], $validated['primary']);
            }
        } catch (\Throwable $e) {
            $httpResult['error_code'] = 'monitoring_failed';
        }

        $ok = (bool) $httpResult['ok'] && ($sslResult['ssl_valid'] === null || $sslResult['ssl_valid'] === true) && empty($httpResult['error_code']);

        if (! empty($httpResult['error_code'])) {
            $ok = false;
        }

        $errorCode = $httpResult['error_code'] ?? $sslResult['error_code'] ?? null;

        if ($ok) {
            $errorCode = null;
        }

        return $this->persist(
            $domain,
            $website,
            $environment,
            $trigger,
            $runId,
            $checkedAt,
            (int) ($httpResult['latency_ms'] ?? 0),
            $httpResult['http_status'] ?? null,
            $ok,
            $sslResult['ssl_valid'] ?? null,
            $sslResult['ssl_expires_at'] ?? null,
            $errorCode,
            $url,
        );
    }

    public function persist(
        WebsiteDomain $domain,
        Website $website,
        ?\App\Models\WebsiteEnvironment $environment,
        string $trigger,
        ?int $runId,
        Carbon $checkedAt,
        int $latencyMs,
        ?int $httpStatus,
        bool $ok,
        ?bool $sslValid,
        $sslExpiresAt,
        ?string $errorCode,
        ?string $targetHost,
    ): ?WebsiteHealthCheck {
        return DB::transaction(function () use ($domain, $website, $environment, $trigger, $runId, $checkedAt, $latencyMs, $httpStatus, $ok, $sslValid, $sslExpiresAt, $errorCode, $targetHost) {
            $state = WebsiteDomainHealthState::query()
                ->where('domain_id', $domain->id)
                ->lockForUpdate()
                ->first();

            $cycleId = $state?->cycle_id;

            if ($trigger === 'scheduled' && $runId !== null && $state?->last_applied_run_id !== null && $runId <= (int) $state->last_applied_run_id) {
                return null;
            }

            try {
                $check = WebsiteHealthCheck::query()->create([
                    'domain_id' => $domain->id,
                    'environment_id' => $environment?->id,
                    'website_id' => $website->id,
                    'trigger_source' => $trigger,
                    'scheduled_run_id' => $trigger === 'scheduled' ? $runId : null,
                    'cycle_id' => $cycleId,
                    'state_at_check' => $state?->state ?? 'operational',
                    'target_host' => $targetHost ? (parse_url($targetHost, PHP_URL_HOST) ?? $targetHost) : null,
                    'http_status' => $httpStatus,
                    'ok' => $ok,
                    'latency_ms' => $latencyMs,
                    'ssl_valid' => $sslValid,
                    'ssl_expires_at' => $sslExpiresAt,
                    'error_code' => $errorCode,
                    'checked_at' => $checkedAt,
                ]);
            } catch (QueryException $e) {
                if (in_array($e->getCode(), ['23505', '23000'], true) && $trigger === 'scheduled' && $runId !== null) {
                    return null;
                }

                throw $e;
            }

            if ($trigger === 'manual') {
                return $check;
            }

            if (! $state) {
                $state = WebsiteDomainHealthState::query()->create([
                    'domain_id' => $domain->id,
                    'website_id' => $website->id,
                    'environment_id' => $environment?->id,
                    'state' => 'operational',
                    'cycle_id' => null,
                    'cycle_number' => 0,
                    'cycle_started_at' => null,
                    'consecutive_failures' => 0,
                    'last_applied_run_id' => $runId,
                    'last_checked_at' => $checkedAt,
                    'last_ok_at' => null,
                    'last_recovered_at' => null,
                ]);
            }

            $state->last_applied_run_id = $runId;
            $state->last_checked_at = $checkedAt;

            if (in_array($errorCode, ['ssrf_blocked', 'ssrf_mismatch', 'monitoring_failed'], true)) {
                $state->state = 'monitoring_error';
                $state->save();

                return $check;
            }

            if ($ok) {
                $wasDown = $state->cycle_id !== null;
                $state->last_ok_at = $checkedAt;
                $state->consecutive_failures = 0;
                $state->state = 'operational';

                if ($wasDown) {
                    $state->last_recovered_at = $checkedAt;
                    $this->resolveAvailabilityAlert($website, $environment, $state->cycle_id, $checkedAt);
                }

                $state->cycle_id = null;
                $state->cycle_started_at = null;
                $state->save();

                $this->syncSslAlert($website, $environment, $sslValid, $sslExpiresAt, $checkedAt);

                return $check;
            }

            $state->consecutive_failures = (int) $state->consecutive_failures + 1;
            $state->state = 'down';

            if ($state->cycle_id === null) {
                $state->cycle_id = (string) \Illuminate\Support\Str::uuid7();
                $state->cycle_number = (int) $state->cycle_number + 1;
                $state->cycle_started_at = $checkedAt;
            }

            $state->save();
            $check->update(['cycle_id' => $state->cycle_id, 'state_at_check' => 'down']);
            $this->syncAvailabilityAlert($website, $environment, $state, $checkedAt);

            $this->syncSslAlert($website, $environment, $sslValid, $sslExpiresAt, $checkedAt);

            return $check;
        });
    }

    protected function syncAvailabilityAlert(Website $website, ?\App\Models\WebsiteEnvironment $environment, WebsiteDomainHealthState $state, Carbon $checkedAt): void
    {
        if ($state->cycle_id === null || ! $environment) {
            return;
        }

        $failures = (int) $state->consecutive_failures;

        if ($failures < 2) {
            return;
        }

        $severity = $failures >= 3 ? 'critical' : 'warning';
        $fingerprint = hash('sha256', implode('|', ['site_indisponivel', \App\Models\WebsiteEnvironment::class, $environment->id, $state->cycle_id]));
        $due = $state->cycle_started_at->copy()->setTimezone('UTC')->toDateString();
        $existing = WebsiteAlert::query()->where('fingerprint', $fingerprint)->first();

        if ($existing && $existing->status === 'open') {
            if ($existing->severity !== $severity) {
                $existing->update(['severity' => $severity]);
                $existing->activities()->create(['user_id' => null, 'event' => 'updated', 'description' => "Severidade atualizada para {$severity}.", 'created_at' => now()]);
            }
            return;
        }

        if ($existing) {
            return;
        }

        try {
            $alert = WebsiteAlert::query()->create([
                'website_id' => $website->id,
                'alert_type' => 'site_indisponivel',
                'severity' => $severity,
                'source_type' => \App\Models\WebsiteEnvironment::class,
                'source_id' => $environment->id,
                'title' => "Site indisponível ({$environment->name})",
                'description' => "Alerta de indisponibilidade iniciado em {$state->cycle_started_at->toDateTimeString()}.",
                'due_at' => $due,
                'fingerprint' => $fingerprint,
                'status' => 'open',
            ]);
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23505', '23000'], true)) {
                return;
            }

            throw $e;
        }

        $alert->activities()->create(['user_id' => null, 'event' => 'created', 'description' => sprintf('Alerta de indisponibilidade criado (%s).', $severity), 'created_at' => now()]);
    }

    protected function resolveAvailabilityAlert(Website $website, ?\App\Models\WebsiteEnvironment $environment, ?string $cycleId, Carbon $checkedAt): void
    {
        if (! $environment || ! $cycleId) {
            return;
        }

        $fingerprint = hash('sha256', implode('|', ['site_indisponivel', \App\Models\WebsiteEnvironment::class, $environment->id, $cycleId]));
        $alert = WebsiteAlert::query()->where('fingerprint', $fingerprint)->where('status', 'open')->first();

        if (! $alert) {
            return;
        }

        $alert->update(['status' => 'resolved', 'resolved_at' => $checkedAt]);
        $alert->activities()->create(['user_id' => null, 'event' => 'auto_resolved', 'description' => 'Site recuperado (verificação agendada OK).', 'created_at' => now()]);
    }

    protected function syncSslAlert(Website $website, ?\App\Models\WebsiteEnvironment $environment, ?bool $sslValid, $sslExpiresAt, Carbon $checkedAt): void
    {
        if (! $environment) {
            return;
        }

        if ($sslValid === true) {
            $this->resolveInvalidSslAlert($website, $environment, $checkedAt);

            $due = $sslExpiresAt ? Carbon::parse($sslExpiresAt)->toDateString() : null;

            if ($due !== null) {
                (new \App\Domain\Websites\Actions\CheckExpirationsAction())->synchronizeExpirationAlert(
                    $website,
                    'ssl_vencendo',
                    \App\Models\WebsiteEnvironment::class,
                    $environment->id,
                    $due,
                    "SSL {$environment->name} vencendo",
                    $checkedAt,
                );
            }

            return;
        }

        if ($sslValid === false) {
            $expiresDate = $sslExpiresAt ? Carbon::parse($sslExpiresAt)->toDateString() : $checkedAt->copy()->setTimezone('UTC')->toDateString();
            $this->createOrKeepInvalidSslAlert($website, $environment, $expiresDate, $checkedAt);
        }
    }

    protected function createOrKeepInvalidSslAlert(Website $website, ?\App\Models\WebsiteEnvironment $environment, string $expiresDate, Carbon $checkedAt): void
    {
        if (! $environment) {
            return;
        }

        $fingerprint = hash('sha256', implode('|', ['ssl_invalido', \App\Models\WebsiteEnvironment::class, $environment->id]));
        $existing = WebsiteAlert::query()->where('fingerprint', $fingerprint)->first();

        if ($existing) {
            return;
        }

        try {
            $alert = WebsiteAlert::query()->create([
                'website_id' => $website->id,
                'alert_type' => 'ssl_invalido',
                'severity' => 'critical',
                'source_type' => \App\Models\WebsiteEnvironment::class,
                'source_id' => $environment->id,
                'title' => "SSL inválido ({$environment->name})",
                'description' => 'Certificado TLS inválido ou hostname incompatível.',
                'due_at' => $expiresDate,
                'fingerprint' => $fingerprint,
                'status' => 'open',
            ]);
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23505', '23000'], true)) {
                return;
            }

            throw $e;
        }

        $alert->activities()->create(['user_id' => null, 'event' => 'created', 'description' => 'Alerta de certificado TLS inválido criado (critical).', 'created_at' => now()]);
    }

    protected function resolveInvalidSslAlert(Website $website, ?\App\Models\WebsiteEnvironment $environment, Carbon $checkedAt): void
    {
        if (! $environment) {
            return;
        }

        $fingerprint = hash('sha256', implode('|', ['ssl_invalido', \App\Models\WebsiteEnvironment::class, $environment->id]));
        $alert = WebsiteAlert::query()->where('fingerprint', $fingerprint)->where('status', 'open')->first();

        if (! $alert) {
            return;
        }

        $alert->update(['status' => 'resolved', 'resolved_at' => $checkedAt]);
        $alert->activities()->create(['user_id' => null, 'event' => 'auto_resolved', 'description' => 'Certificado TLS válido novamente.', 'created_at' => now()]);
    }

    protected function buildUrl(WebsiteDomain $domain): ?string
    {
        if ($domain->status !== 'ativo') {
            return null;
        }

        $host = trim((string) $domain->domain);

        if ($host === '') {
            return null;
        }

        return 'https://'.$host.'/';
    }

    protected function primaryIpForUrl(string $url): ?array
    {
        try {
            return $this->guard->parseUrl($url);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
