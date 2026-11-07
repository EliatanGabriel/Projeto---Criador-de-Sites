<?php

namespace App\Domain\Websites\Actions;

use App\Models\Hosting;
use App\Models\Website;
use App\Models\WebsiteAlert;
use App\Models\WebsiteDomain;
use App\Models\WebsiteEnvironment;
use Illuminate\Support\Facades\DB;

class CheckExpirationsAction
{
    public const THRESHOLDS = [
        ['days' => 30, 'severity' => 'info'],
        ['days' => 15, 'severity' => 'warning'],
        ['days' => 7, 'severity' => 'critical'],
        ['days' => 1, 'severity' => 'critical'],
        ['days' => 0, 'severity' => 'critical'],
    ];

    public function severityFor(string $dueAt, \Carbon\CarbonInterface $today): string
    {
        $due = \Carbon\Carbon::parse($dueAt)->startOfDay();
        $todayStart = $today->copy()->startOfDay();
        $delta = $due->diffInDays($todayStart, false); // signed: $due - $today (Carbon retorna positivo quando $due é futuro em $today->diffInDays)

        if ($delta < 0) {
            // Data de vencimento em futuro: calcular dias restantes
            $daysRemaining = -$delta; // positivo
            if ($daysRemaining <= 1) return 'critical';
            if ($daysRemaining <= 7) return 'critical';
            if ($daysRemaining <= 15) return 'warning';
            if ($daysRemaining <= 30) return 'info';
            return 'none';
        } else {
            // Vencido ou hoje
            return 'critical';
        }
    }

    public function synchronizeExpirationAlert(Website $website, string $alertType, string $sourceType, string $sourceId, $dueAt, string $title, \Carbon\CarbonInterface $today): bool
    {
        return $this->evaluate($website, $alertType, $sourceType, $sourceId, $dueAt, $title, $today);
    }

    public function __invoke(?\Carbon\CarbonInterface $now = null): array
    {
        $today = ($now ?? now())->copy()->startOfDay();
        $stats = ['domains' => 0, 'ssl' => 0, 'hostings' => 0];

        DB::transaction(function () use ($today, &$stats) {
            foreach (WebsiteDomain::query()->withTrashed(false)->whereNotNull('expires_at')->where('expires_at', '<=', $today->copy()->addDays(30)->toDateString())->get() as $domain) {
                if ($this->evaluate(
                    website: Website::find($domain->website_id),
                    alertType: 'dominio_vencendo',
                    sourceType: WebsiteDomain::class,
                    sourceId: $domain->id,
                    dueAt: $domain->expires_at,
                    title: "Domínio {$domain->domain} vencendo",
                    today: $today,
                )) $stats['domains']++;
            }

            foreach (WebsiteEnvironment::query()->withTrashed(false)->whereNotNull('ssl_expires_at')->where('ssl_expires_at', '<=', $today->copy()->addDays(30)->toDateString())->get() as $env) {
                if ($this->evaluate(
                    website: Website::find($env->website_id),
                    alertType: 'ssl_vencendo',
                    sourceType: WebsiteEnvironment::class,
                    sourceId: $env->id,
                    dueAt: $env->ssl_expires_at,
                    title: "SSL {$env->name} vencendo",
                    today: $today,
                )) $stats['ssl']++;
            }

            $usedHostingIds = WebsiteEnvironment::query()->withTrashed(false)->distinct()->pluck('hosting_id')->filter();
            foreach (Hosting::query()->withTrashed(false)->whereIn('id', $usedHostingIds)->whereNotNull('next_renewal_at')->where('next_renewal_at', '<=', $today->copy()->addDays(30)->toDateString())->get() as $hosting) {
                foreach ($hosting->environments()->withTrashed(false)->get() as $env) {
                    $site = $env->website()->first();
                    if ($this->evaluate(
                        website: $site,
                        alertType: 'hosting_vencendo',
                        sourceType: Hosting::class,
                        sourceId: $hosting->id,
                        dueAt: $hosting->next_renewal_at,
                        title: "Hospedagem {$hosting->name} renovando",
                        today: $today,
                    )) $stats['hostings']++;
                }
            }

            // auto-resolve stale: alertas abertos cuja fingerprint não corresponde a nada atual e devido já venceu há >0? já resolvemos na avaliação
        });

        return $stats;
    }

    protected function evaluate(?Website $website, string $alertType, string $sourceType, string $sourceId, $dueAt, string $title, \Carbon\CarbonInterface $today): bool
    {
        if (! $website || $website->trashed()) return false;
        $due = $dueAt ? \Carbon\Carbon::parse($dueAt)->toDateString() : null;
        if (! $due) return false;
        $severity = $this->severityFor($due, $today);
        if ($severity === 'none') return false;

        $fingerprint = hash('sha256', implode('|', [$alertType, $sourceType, $sourceId, $due]));
        $existing = WebsiteAlert::where('fingerprint', $fingerprint)->first();

        if ($existing && $existing->status === 'open') {
            if ($existing->severity !== $severity) {
                $existing->update(['severity' => $severity]);
                $existing->activities()->create([
                    'user_id' => null,
                    'event' => 'updated',
                    'description' => "Severidade atualizada para {$severity}.",
                    'created_at' => now(),
                ]);
            }
            return true;
        }

        if ($existing && in_array($existing->status, ['resolved', 'dismissed'], true)) {
            return false;
        }

        // verifica resolvido/dispensado anterior para não duplicar histórico: se já existe histórico resolvido com mesmo fingerprint, não reabrir
        $historyExists = WebsiteAlert::where('fingerprint', $fingerprint)->exists();
        if ($historyExists) return false;

        try {
            $alert = WebsiteAlert::create([
                'website_id' => $website->id,
                'alert_type' => $alertType,
                'severity' => $severity,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'title' => $title,
                'description' => "Vence em {$due}",
                'due_at' => $due,
                'fingerprint' => $fingerprint,
                'status' => 'open',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23505' || $e->getCode() === '23000') return true;
            throw $e;
        }

        $alert->activities()->create([
            'user_id' => null,
            'event' => 'created',
            'description' => sprintf('Alerta criado (%s) para vencimento em %s.', $severity, $due),
            'created_at' => now(),
        ]);

        return true;
    }
}
