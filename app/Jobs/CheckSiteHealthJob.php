<?php

namespace App\Jobs;

use App\Models\SiteHealthRun;
use App\Models\WebsiteDomain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckSiteHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const RECOVERY_WINDOW_DAYS = 7;

    public const DISPATCH_STALE_MINUTES = 10;

    public int $tries = 3;

    public int $timeout = 120;

    public function middleware(): array
    {
        return [new WithoutOverlapping('site-health')];
    }

    public function handle(): void
    {
        foreach ($this->windowSlots() as $slot) {
            $this->allocateRuns($slot);
        }
    }

    public function allocateRuns(string $slot): void
    {
        foreach ($this->eligibleDomains($slot) as $domain) {
            try {
                $this->dispatchRun((string) $domain->id, (string) $domain->website_id, $slot);
            } catch (\Throwable $e) {
                Log::warning('site-health reconcile failed', ['domain' => $domain->id, 'slot' => $slot, 'error' => $e->getMessage()]);
            }
        }
    }

    public function windowSlots(): array
    {
        $slots = [];
        $base = now('America/Sao_Paulo')->startOfDay();

        for ($i = self::RECOVERY_WINDOW_DAYS - 1; $i >= 0; $i--) {
            $slots[] = $base->copy()->subDays($i)->toDateString();
        }

        return $slots;
    }

    protected function dispatchRun(string $domainId, string $websiteId, string $slot): void
    {
        $run = $this->recoverOrCreateRun($domainId, $websiteId, $slot);

        DB::transaction(function () use ($domainId, $websiteId, $slot, $run) {
            $locked = SiteHealthRun::query()
                ->where('scheduled_run_id', $run->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            if ($locked->status === 'applied') {
                return;
            }

            if ($locked->status === 'dispatched' && $locked->dispatched_at && $locked->dispatched_at->gt(now()->subMinutes(self::DISPATCH_STALE_MINUTES))) {
                return;
            }

            CheckWebsiteHealthJob::dispatch($websiteId, $domainId, (int) $locked->getKey());

            $locked->forceFill(['status' => 'dispatched', 'dispatched_at' => now()])->save();
        });
    }

    protected function recoverOrCreateRun(string $domainId, string $websiteId, string $slot): SiteHealthRun
    {
        try {
            return SiteHealthRun::query()->firstOrCreate(
                ['domain_id' => $domainId, 'scheduled_for' => $slot],
                ['website_id' => $websiteId, 'status' => 'pending'],
            );
        } catch (QueryException $e) {
            if (! in_array($e->getCode(), ['23505', '23000'], true)) {
                throw $e;
            }

            return SiteHealthRun::query()->where('domain_id', $domainId)->where('scheduled_for', $slot)->firstOrFail();
        }
    }

    protected function eligibleDomains(string $slot): iterable
    {
        return WebsiteDomain::query()
            ->where('status', 'ativo')
            ->whereDate('website_domains.created_at', '<=', $slot)
            ->where(function ($q) use ($slot) {
                $q->whereNull('deleted_at')->orWhereDate('deleted_at', '>', $slot);
            })
            ->whereHas('website', fn ($q) => $q->withTrashed(false)->whereDate('websites.created_at', '<=', $slot)->whereHas('environments', fn ($qq) => $qq->where('type', 'production')->where('status', 'ativo')->whereNull('deleted_at')))
            ->get();
    }
}
