<?php

use App\Jobs\CheckExpirationsJob;
use App\Jobs\CheckSiteHealthJob;
use App\Jobs\PruneWebsiteHealthChecksJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckExpirationsJob)->dailyAt('07:00')->timezone('America/Sao_Paulo')->withoutOverlapping();
Schedule::job(new CheckSiteHealthJob)->dailyAt('06:30')->timezone('America/Sao_Paulo')->withoutOverlapping();
Schedule::job(new PruneWebsiteHealthChecksJob)->monthlyOn(1, '05:00')->timezone('America/Sao_Paulo');

