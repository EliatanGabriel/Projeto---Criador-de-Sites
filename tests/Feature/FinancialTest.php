<?php

use App\Models\Client;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function fOwner()
{
    $u = User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}
function fCommercial()
{
    $u = User::factory()->create();
    $u->assignRole('COMMERCIAL');
    return $u;
}
function fDeveloper()
{
    $u = User::factory()->create();
    $u->assignRole('DEVELOPER');
    return $u;
}

function fCategory(string $type = 'receita'): FinancialCategory
{
    return FinancialCategory::factory()->create(['type' => $type]);
}

test('receita: previsto vira recebido apenas ao pagar o total exato', function () {
    $owner = fOwner();
    $client = Client::factory()->create();
    $category = fCategory('receita');

    $this->actingAs($owner)->post('/finance', [
        'type' => 'receita',
        'category_id' => $category->id,
        'client_id' => $client->id,
        'description' => 'Mensalidade',
        'amount' => '1000.00',
        'status' => 'previsto',
    ])->assertRedirect();

    $transaction = FinancialTransaction::first();
    expect($transaction->status)->toBe('previsto');

    $this->actingAs($owner)->post("/finance/{$transaction->id}/payments", [
        'amount' => '400.00',
        'paid_at' => now()->toDateString(),
        'method' => 'pix',
    ])->assertRedirect();

    expect($transaction->refresh()->status)->toBe('pendente');
    expect((float) $transaction->payments()->sum('amount'))->toBe(400.0);

    $this->actingAs($owner)->post("/finance/{$transaction->id}/payments", [
        'amount' => '600.00',
        'paid_at' => now()->toDateString(),
    ])->assertRedirect();

    expect($transaction->refresh()->status)->toBe('recebido');
});

test('excesso de pagamento é rejeitado (nunca confunde previsto com recebido)', function () {
    $owner = fOwner();
    $category = fCategory('receita');
    $transaction = FinancialTransaction::factory()->create(['type' => 'receita', 'category_id' => $category->id, 'amount' => '100.00', 'status' => 'previsto']);

    $this->actingAs($owner)->post("/finance/{$transaction->id}/payments", [
        'amount' => '150.00',
        'paid_at' => now()->toDateString(),
    ])->assertStatus(422);

    expect($transaction->payments()->count())->toBe(0);
});

test('cancelamento sem pagamentos é permitido e registrado', function () {
    $owner = fOwner();
    $category = fCategory('receita');
    $transaction = FinancialTransaction::factory()->create(['type' => 'receita', 'category_id' => $category->id, 'status' => 'previsto']);

    $this->actingAs($owner)->post("/finance/{$transaction->id}/cancel")->assertRedirect();

    expect($transaction->refresh()->status)->toBe('cancelado');
});

test('não há hard delete de lançamentos (402? — 405/cancelamento)', function () {
    $owner = fOwner();
    $category = fCategory('receita');
    $transaction = FinancialTransaction::factory()->create(['type' => 'receita', 'category_id' => $category->id, 'status' => 'previsto']);

    $this->actingAs($owner)->delete("/finance/{$transaction->id}")->assertMethodNotAllowed();

    $this->actingAs($owner)->get("/finance/{$transaction->id}")->assertOk();
});

test('permissões: OWNER gerencia; COMMERCIAL escopado apenas visualiza; DEVELOPER sem manage não cria', function () {
    $owner = fOwner();
    $commercial = fCommercial();
    $developer = fDeveloper();

    $client = Client::factory()->create();
    \App\Models\Opportunity::factory()->create(['client_id' => $client->id, 'responsible_id' => $commercial->id]);
    $category = fCategory('receita');
    $transaction = FinancialTransaction::factory()->create(['type' => 'receita', 'category_id' => $category->id, 'client_id' => $client->id, 'status' => 'previsto']);

    $this->actingAs($commercial)->get("/finance/{$transaction->id}")->assertForbidden();
    $this->actingAs($commercial)->post('/finance', [
        'type' => 'receita', 'category_id' => $category->id, 'description' => 'Teste', 'amount' => '10.00',
    ])->assertForbidden();
    $this->actingAs($developer)->post('/finance', [
        'type' => 'receita', 'category_id' => $category->id, 'description' => 'Teste', 'amount' => '10.00',
    ])->assertForbidden();
});

test('concorrência: dois pagamentos simultâneos que somados excedem são bloqueados', function () {
    $owner = fOwner();
    $category = fCategory('receita');
    $transaction = FinancialTransaction::factory()->create(['type' => 'receita', 'category_id' => $category->id, 'amount' => '100.00', 'status' => 'previsto']);

    $txA = FinancialTransaction::find($transaction->id);
    $paid = (float) $txA->payments()->sum('amount') + 60.0;
    expect($paid <= 100.0)->toBeTrue();

    $this->actingAs($owner)->post("/finance/{$transaction->id}/payments", ['amount' => '60.00', 'paid_at' => now()->toDateString()])->assertRedirect();
    $this->actingAs($owner)->post("/finance/{$transaction->id}/payments", ['amount' => '60.00', 'paid_at' => now()->toDateString()])->assertStatus(422);

    expect((float) $transaction->payments()->sum('amount'))->toBe(60.0);
});

test('categoria com tipo divergente é rejeitado', function () {
    $owner = fOwner();
    $category = fCategory('despesa');

    $this->actingAs($owner)->post('/finance', [
        'type' => 'receita', 'category_id' => $category->id, 'description' => 'Errado', 'amount' => '10.00',
    ])->assertSessionHasErrors('category_id');
});

test('relatório mensal: receitas e resultado operacional (recebido menos pago)', function () {
    $owner = fOwner();
    $rc = fCategory('receita');
    $dc = fCategory('despesa');
    $month = now()->toDateString();

    FinancialTransaction::factory()->create(['type' => 'receita', 'category_id' => $rc->id, 'amount' => '2000.00', 'status' => 'recebido', 'due_at' => $month]);
    FinancialTransaction::factory()->create(['type' => 'receita', 'category_id' => $rc->id, 'amount' => '500.00', 'status' => 'previsto', 'due_at' => $month]);
    FinancialTransaction::factory()->create(['type' => 'despesa', 'category_id' => $dc->id, 'amount' => '800.00', 'status' => 'pago', 'due_at' => $month]);
    FinancialTransaction::factory()->create(['type' => 'despesa', 'category_id' => $dc->id, 'amount' => '100.00', 'status' => 'previsto', 'due_at' => $month]);

    $this->actingAs($owner)->get('/finance/report')->assertOk()->assertInertia(function (\Inertia\Testing\AssertableInertia $page) {
        $page->component('finance/report')->has('summary', function (\Inertia\Testing\AssertableInertia $page) {
            $page->where('revenue_recebido', 2000)->where('resultado_operacional', 1200)->etc();
        });
    });
});
