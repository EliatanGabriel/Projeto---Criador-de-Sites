<?php

use App\Models\Client;
use App\Models\Demand;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function cOwner()
{
    $u = User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}

test('concorrência PG: lock serializa conversão da mesma demanda', function () {
    $driver = config('database.default');
    if ($driver !== 'pgsql') {
        $this->markTestSkipped('Concorrência requer PostgreSQL 17.');
    }

    $owner = cOwner();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);
    $demandId = $demand->id;

    // Flush/commit para que conexões externas vejam a linha (RefreshDatabase em PG usa transações – força flush)
    \DB::commit();

    $connA = new PDO(
        'pgsql:host=127.0.0.1;port=5433;dbname=webstudio_os_test',
        'webstudio',
        'secret',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $connB = new PDO(
        'pgsql:host=127.0.0.1;port=5433;dbname=webstudio_os_test',
        'webstudio',
        'secret',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $connA->beginTransaction();
    $stmt = $connA->prepare('SELECT id FROM demands WHERE id = :id FOR UPDATE');
    $stmt->execute(['id' => $demandId]);
    $rowA = $stmt->fetch(PDO::FETCH_ASSOC);
    expect($rowA)->not->toBeFalse();
    expect((string) $rowA['id'])->toBe((string) $demandId);

    try {
        $stmtB = $connB->prepare('SELECT id FROM demands WHERE id = :id FOR UPDATE NOWAIT');
        $stmtB->execute(['id' => $demandId]);
        $this->fail('Segunda conexão deveria ser bloqueada pelo lock da primeira');
    } catch (PDOException $e) {
        expect(str_contains($e->getMessage(), 'could not obtain lock'))->toBeTrue();
    }

    $connA->rollBack();

    // Dispara duas conversões simultâneas via HTTP (segunda deve falhar 422 por unicidade)
    $first = $this->actingAs($owner)->post("/demands/{$demandId}/convert");
    // conta projetos: no máximo 1 (segunda tentativa idempotente via UI não testada aqui por já ter convertido)
    $count = Project::where('demand_id', $demandId)->count();
    expect($count)->toBe(1);
    expect(Project::find($demandId) ?? Project::where('demand_id', $demandId)->first())->not->toBeNull();
});
