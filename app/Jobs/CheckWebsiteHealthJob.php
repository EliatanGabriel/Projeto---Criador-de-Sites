<?php

namespace App\Jobs;

use App\Domain\Websites\Actions\RunSiteHealthCheckAction;
use App\Models\SiteHealthRun;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Models\WebsiteEnvironment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class CheckWebsiteHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly string $websiteId, public readonly string $domainId, public readonly int $scheduledRunId) {}

    public function middleware(): array
    {
        return [new WithoutOverlapping('health:'.$this->domainId)];
    }

    public function handle(RunSiteHealthCheckAction $action): void
    {
        $run = SiteHealthRun::query()->find($this->scheduledRunId);

        if (! $run || $run->status === 'applied') {
            return;
        }

        $domain = WebsiteDomain::query()->withTrashed(false)->where('id', $this->domainId)->first();

        if (! $domain || $domain->status !== 'ativo') {
            $run->forceFill(['status' => 'applied', 'applied_at' => now()])->save();

            return;
        }

        $website = Website::query()->find($this->websiteId);

        if (! $website || $website->trashed()) {
            $run->forceFill(['status' => 'applied', 'applied_at' => now()])->save();

            return;
        }

        $environment = WebsiteEnvironment::query()
            ->where('website_id', $website->id)
            ->where('type', 'production')
            ->where('status', 'ativo')
            ->whereNull('deleted_at')
            ->first();

        $action->checkDomain($domain, $website, $environment, $this->scheduledRunId, 'scheduled');

        $run->forceFill(['status' => 'applied', 'applied_at' => now()])->save();
    }
}
