<?php

use App\Models\Client;
use App\Models\Demand;
use App\Models\Opportunity;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

function commercialUserSale()
{
    $u = User::factory()->create();
    $u->assignRole('COMMERCIAL');
    return $u;
}
function ownerUserSale()
{
    $u = User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}
function developerUserSale()
{
    $u = User::factory()->create();
    $u->assignRole('DEVELOPER');
    return $u;
}

test('criação de demanda com briefing e sem oportunidade', function () {
    $commercial = commercialUserSale();
    $client = Client::factory()->create();

    $response = $this->actingAs($commercial)->post('/demands', [
        'title' => 'Site novo',
        'client_id' => $client->id,
        'service_type' => 'Site institucional',
        'description' => 'Redesign completo',
        'priority' => 'alta',
        'briefing' => ['objectives' => 'Aumentar vendas'],
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('demands', ['title' => 'Site novo', 'status' => 'draft', 'client_id' => $client->id]);
    $d = Demand::first();
    expect($d->briefing->objectives)->toBe('Aumentar vendas');
    $this->assertDatabaseHas('demand_histories', ['demand_id' => $d->id, 'event' => 'created']);
});

test('validação: oportunidade deve pertencer ao cliente', function () {
    $commercial = commercialUserSale();
    $clientA = Client::factory()->create();
    $clientB = Client::factory()->create();
    $opp = Opportunity::factory()->create(['client_id' => $clientA->id]);

    $this->actingAs($commercial)->post('/demands', [
        'title' => 'Erro',
        'client_id' => $clientB->id,
        'opportunity_id' => $opp->id,
        'priority' => 'normal',
    ])->assertSessionHasErrors('opportunity_id');
});

test('upload de anexo valida tipo e tamanho', function () {
    $commercial = commercialUserSale();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'created_by' => $commercial->id, 'commercial_owner_id' => $commercial->id, 'status' => 'draft']);

    // tipo inválido
    $this->actingAs($commercial)->post("/demands/{$demand->id}/attachments", [
        'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
    ])->assertSessionHasErrors('file');

    // válido
    $this->actingAs($commercial)->post("/demands/{$demand->id}/attachments", [
        'file' => UploadedFile::fake()->create('logo.png', 100, 'image/png'),
    ])->assertRedirect();
    $this->assertDatabaseHas('demand_attachments', ['demand_id' => $demand->id, 'original_name' => 'logo.png']);
});

test('download autorizado e não autorizado', function () {
    $commercial = commercialUserSale();
    $developer = developerUserSale();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'draft', 'commercial_owner_id' => $commercial->id, 'created_by' => $commercial->id]);
    $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');
    $path = $file->store('demand-attachments', 'local');
    $att = $demand->attachments()->create(['disk' => 'local', 'path' => $path, 'original_name' => 'doc.pdf', 'mime' => 'application/pdf', 'size' => 51200, 'uploaded_by' => $commercial->id]);

    $this->actingAs($commercial)->get("/demands/{$demand->id}/attachments/{$att->id}/download")->assertOk()->assertHeader('Content-Disposition');
    $this->actingAs($developer)->get("/demands/{$demand->id}/attachments/{$att->id}/download")->assertForbidden();
});

test('envio para análise valida campos obrigatórios', function () {
    $commercial = commercialUserSale();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'draft', 'commercial_owner_id' => $commercial->id]);
    $demand->update(['service_type' => null, 'description' => null]);

    $this->actingAs($commercial)->post("/demands/{$demand->id}/submit")->assertStatus(422);
});

test('fluxo completo: enviar, iniciar análise, solicitar info, reenviar, aprovar', function () {
    $commercial = commercialUserSale();
    $owner = ownerUserSale();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'title' => 'Fluxo', 'service_type' => 'Site', 'description' => 'Desc', 'status' => 'draft', 'commercial_owner_id' => $commercial->id, 'created_by' => $commercial->id]);

    $this->actingAs($commercial)->post("/demands/{$demand->id}/submit")->assertRedirect();
    expect(Demand::find($demand->id)->status)->toBe('submitted');
    expect(Demand::find($demand->id)->submitted_at)->not->toBeNull();

    $this->actingAs($owner)->post("/demands/{$demand->id}/start-review")->assertRedirect();
    expect(Demand::find($demand->id)->status)->toBe('under_review');
    expect(Demand::find($demand->id)->technical_owner_id)->toBe($owner->id);

    $this->actingAs($owner)->post("/demands/{$demand->id}/request-information", ['reason' => 'Falta briefing'])->assertRedirect();
    expect(Demand::find($demand->id)->status)->toBe('needs_information');

    // commercial edita e reenvia
    $this->actingAs($commercial)->put("/demands/{$demand->id}", [
        'title' => 'Fluxo complementado', 'client_id' => $client->id, 'service_type' => 'Site', 'description' => 'Desc completa', 'priority' => 'normal',
    ])->assertRedirect();
    $this->actingAs($commercial)->post("/demands/{$demand->id}/submit")->assertRedirect();
    expect(Demand::find($demand->id)->status)->toBe('submitted');

    $this->actingAs($owner)->post("/demands/{$demand->id}/start-review")->assertRedirect();
    $this->actingAs($owner)->post("/demands/{$demand->id}/approve", ['notes' => 'OK'])->assertRedirect();
    expect(Demand::find($demand->id)->status)->toBe('approved');
    expect(Demand::find($demand->id)->approved_at)->not->toBeNull();

    $this->assertDatabaseHas('demand_histories', ['demand_id' => $demand->id, 'to_status' => 'approved']);
});

test('recusa exige justificativa', function () {
    $owner = ownerUserSale();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'under_review']);

    $this->actingAs($owner)->post("/demands/{$demand->id}/reject", [])->assertSessionHasErrors('reason');
    $this->actingAs($owner)->post("/demands/{$demand->id}/reject", ['reason' => 'Inviável'])->assertRedirect();
    expect(Demand::find($demand->id)->status)->toBe('rejected');
});

test('cancelamento registra responsável e motivo', function () {
    $owner = ownerUserSale();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'draft']);

    $this->actingAs($owner)->post("/demands/{$demand->id}/cancel", ['reason' => 'Cliente pediu'])->assertRedirect();
    expect(Demand::find($demand->id)->status)->toBe('cancelled');
    $this->assertDatabaseHas('demand_histories', ['demand_id' => $demand->id, 'to_status' => 'cancelled']);
});

test('transições inválidas são bloqueadas', function () {
    $commercial = commercialUserSale();
    $owner = ownerUserSale();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'draft']);

    // aprovar direto de draft é inválido (403 por policy antes do 422 da transição)
    $this->actingAs($owner)->post("/demands/{$demand->id}/approve", [])->assertForbidden();

    // approved não pode voltar para draft
    $approved = Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved']);
    $this->actingAs($commercial)->post("/demands/{$approved->id}/submit")->assertForbidden();
});

test('proteção por perfil: commercial não aprova, developer só acessa demanda do seu projeto', function () {
    $commercial = commercialUserSale();
    $developer = developerUserSale();
    $client = Client::factory()->create();
    $demand = Demand::factory()->create(['client_id' => $client->id, 'status' => 'under_review']);

    $this->actingAs($commercial)->post("/demands/{$demand->id}/approve", [])->assertForbidden();
    $this->actingAs($developer)->get('/demands')->assertOk();
    $this->actingAs($developer)->get("/demands/{$demand->id}")->assertForbidden();
    $this->actingAs($developer)->post("/demands/{$demand->id}/approve", [])->assertForbidden();
});

test('listagem filtra por status e cliente', function () {
    $commercial = commercialUserSale();
    $client = Client::factory()->create();
    Demand::factory()->create(['client_id' => $client->id, 'status' => 'draft', 'commercial_owner_id' => $commercial->id]);
    Demand::factory()->create(['client_id' => $client->id, 'status' => 'approved', 'commercial_owner_id' => $commercial->id]);

    $this->actingAs($commercial)->get('/demands?status=draft')->assertOk()->assertInertia(fn ($p) => $p->component('demands/index'));
    $this->actingAs($commercial)->get("/demands?client_id={$client->id}")->assertOk();
});
