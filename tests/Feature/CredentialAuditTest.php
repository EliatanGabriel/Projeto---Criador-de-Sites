<?php

use App\Models\Credential;
use App\Models\Hosting;
use App\Models\User;
use App\Models\Website;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aOwner2()
{
    $u = User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}

test('CHECK constraint rejeita zero e duas+ referências direto no banco', function () {
    // PG aborta transação no erro – isola cada inserção inválida em savepoint
    $site = Website::factory()->create();
    $hosting = Hosting::factory()->create();

    // zero referências
    $failedZero = false;
    try {
        DB::transaction(function () use ($site, $hosting) {
            DB::table('credentials')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid7(),
                'website_id' => null,
                'hosting_id' => null,
                'domain_id' => null,
                'environment_id' => null,
                'type' => 'cms',
                'label' => 'Zero',
                'secret_encrypted' => Crypt::encryptString('x-segredo'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    } catch (QueryException $e) {
        $failedZero = true;
    }
    expect($failedZero)->toBeTrue();

    // duas referências
    $failedTwo = false;
    try {
        DB::transaction(function () use ($site, $hosting) {
            DB::table('credentials')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid7(),
                'website_id' => $site->id,
                'hosting_id' => $hosting->id,
                'type' => 'cms',
                'label' => 'Duas',
                'secret_encrypted' => Crypt::encryptString('x-segredo'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    } catch (QueryException $e) {
        $failedTwo = true;
    }
    expect($failedTwo)->toBeTrue();

    // exatamente uma funciona direto no banco
    $id = (string) \Illuminate\Support\Str::uuid7();
    DB::table('credentials')->insert([
        'id' => $id,
        'website_id' => $site->id,
        'type' => 'cms',
        'label' => 'Uma',
        'secret_encrypted' => Crypt::encryptString('x-segredo'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->assertDatabaseHas('credentials', ['id' => $id, 'label' => 'Uma']);
});

test('logs created/updated/revealed sem segredo', function () {
    $owner = aOwner2();
    $site = Website::factory()->create();

    // created
    $this->actingAs($owner)->post('/credentials', [
        'website_id' => $site->id,
        'type' => 'cms',
        'label' => 'CMS',
        'secret' => 'segredo-plano-auditoria',
    ])->assertRedirect();
    $cred = Credential::first();
    $this->assertDatabaseHas('credential_access_logs', ['credential_id' => $cred->id, 'action' => 'created', 'user_id' => $owner->id]);

    // updated sem novo secret também audita
    $this->actingAs($owner)->put("/credentials/{$cred->id}", [
        'label' => 'CMS 2',
    ])->assertRedirect();
    $this->assertDatabaseHas('credential_access_logs', ['credential_id' => $cred->id, 'action' => 'updated', 'user_id' => $owner->id]);

    // revealed
    $this->actingAs($owner)->postJson("/credentials/{$cred->id}/reveal", ['password' => 'password'])->assertOk();
    $this->assertDatabaseHas('credential_access_logs', ['credential_id' => $cred->id, 'action' => 'revealed', 'user_id' => $owner->id]);

    // nenhum log contém o segredo em texto puro ou criptografado?
    $logs = \DB::table('credential_access_logs')->where('credential_id', $cred->id)->get();
    foreach ($logs as $log) {
        $serialized = json_encode($log);
        $this->assertStringNotContainsString('segredo-plano-auditoria', $serialized);
        $raw = DB::table('credentials')->where('id', $cred->id)->value('secret_encrypted');
        $this->assertStringNotContainsString($raw, $serialized);
    }
});

test('rate limiting na revelação (5/min)', function () {
    $owner = aOwner2();
    $site = Website::factory()->create();
    $cred = Credential::factory()->create(['website_id' => $site->id]);
    $cred->secret_encrypted = 'segredo';
    $cred->save();

    for ($i = 1; $i <= 5; $i++) {
        $this->actingAs($owner)->postJson("/credentials/{$cred->id}/reveal", ['password' => 'password'])->assertOk();
    }
    // 6ª tentativa dentro da janela deve ser 429 (throttleniosk:5,1)
    $this->actingAs($owner)->postJson("/credentials/{$cred->id}/reveal", ['password' => 'password'])->assertStatus(429);
});

test('senha incorreta não revela e 403/422 preservados', function () {
    $owner = aOwner2();
    $commercial = User::factory()->create();
    $commercial->assignRole('COMMERCIAL');
    $site = Website::factory()->create();
    $cred = Credential::factory()->create(['website_id' => $site->id]);
    $cred->secret_encrypted = 'segredo';
    $cred->save();

    // senha incorreta → 422 e não revela
    $resp = $this->actingAs($owner)->postJson("/credentials/{$cred->id}/reveal", ['password' => 'errada']);
    $resp->assertStatus(422);
    $resp->assertJsonMissing(['secret']);

    // sem permissão → 403
    $this->actingAs($commercial)->postJson("/credentials/{$cred->id}/reveal", ['password' => 'password'])->assertForbidden();

    // Cache-Control no-store na revelação autorizada
    $ok = $this->actingAs($owner)->postJson("/credentials/{$cred->id}/reveal", ['password' => 'password']);
    $ok->assertOk();
    $ok->assertHeader('Cache-Control', 'no-store, private');
});
