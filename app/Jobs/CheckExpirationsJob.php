<?php

namespace App\Jobs;

use App\Domain\Websites\Actions\CheckExpirationsAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckExpirationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function middleware(): array
    {
        return [new WithoutOverlapping('check-expirations')];
    }

    public function handle(CheckExpirationsAction $action): void
    {
        $stats = $action();
        Log::info('[check-expirations]', $stats);
    }
}
