<?php

use App\Domain\Projects\Actions\ConvertDemandToProjectAction;
use App\Models\Demand;
use App\Models\User;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$demandId = $argv[1] ?? null;
$ownerId = $argv[2] ?? null;

if (! $demandId || ! $ownerId) {
    fwrite(STDERR, "Uso: concurrency_worker.php <demandId> <ownerId>\n");
    exit(2);
}

try {
    $demand = Demand::findOrFail($demandId);
    $owner = User::findOrFail($ownerId);
    // pequena barreira para sobrepor execução
    usleep(random_int(50_000, 150_000));
    $action = app(ConvertDemandToProjectAction::class);
    $project = $action($demand, $owner);
    echo "SUCCESS:{$project->id}\n";
    exit(0);
} catch (\Throwable $e) {
    echo "FAIL:".get_class($e).":".$e->getMessage()."\n";
    exit(1);
}
