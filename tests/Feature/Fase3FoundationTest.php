<?php

use App\Models\Client;
use App\Models\FinancialTransaction;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('fase3: homePanel por role sem nome', function () {
    $owner = User::factory()->create(['name' => 'Eliatan Gabriel']);
    $owner->assignRole('OWNER');
    $dev = User::factory()->create(['name' => 'Roger Pereira']);
    $dev->assignRole('DEVELOPER');
    $com = User::factory()->create(['name' => 'Alisson']);
    $com->assignRole('COMMERCIAL');
    expect($owner->homePanelPath())->toBe('/admin');
    expect($dev->homePanelPath())->toBe('/developer');
    expect($com->homePanelPath())->toBe('/commercial');
});

test('fase3: commercial ve financeiro apenas do proprio cliente', function () {
    $com = User::factory()->create();
    $com->assignRole('COMMERCIAL');
    $other = User::factory()->create();
    $other->assignRole('COMMERCIAL');
    $c1 = Client::factory()->create();
    $c2 = Client::factory()->create();
    Opportunity::factory()->create(['client_id' => $c1->id, 'responsible_id' => $com->id]);
    $t1 = FinancialTransaction::factory()->create(['client_id' => $c1->id, 'type' => 'receita', 'status' => 'previsto', 'amount' => 100]);
    $t2 = FinancialTransaction::factory()->create(['client_id' => $c2->id, 'type' => 'receita', 'status' => 'previsto', 'amount' => 200]);
    $this->actingAs($com)->get("/finance/{$t1->id}")->assertOk();
    $this->actingAs($com)->get("/finance/{$t2->id}")->assertForbidden();
    $this->actingAs($com)->get('/finance/report')->assertOk();
});

test('fase3: developer resolve alerta no escopo', function () {
    $dev = User::factory()->create(['name' => 'Roger Pereira']);
    $dev->assignRole('DEVELOPER');
    $client = Client::factory()->create();
    $project = \App\Models\Project::factory()->create(['client_id' => $client->id, 'technical_owner_id' => $dev->id]);
    $site = \App\Models\Website::factory()->create(['client_id' => $client->id]);
    $site->websiteProjects()->create(['project_id' => $project->id, 'relation_type' => 'origin']);
    $alert = \App\Models\WebsiteAlert::factory()->create(['website_id' => $site->id, 'status' => 'open']);
    $this->actingAs($dev)->post("/alerts/{$alert->id}/resolve")->assertRedirect();
    expect($alert->fresh()->status)->toBe('resolved');
});

test('fase3: unique clients document e email', function () {
    $c = Client::factory()->create(['document' => '12345678000199', 'email' => 'dup@teste.com']);
    expect(fn () => Client::factory()->create(['document' => '12345678000199', 'email' => 'outro@teste.com']))->toThrow(\Illuminate\Database\QueryException::class);
    expect(fn () => Client::factory()->create(['document' => '99999999000199', 'email' => 'dup@teste.com']))->toThrow(\Illuminate\Database\QueryException::class);
});

test('fase3: developer nao acessa /finance', function () {
    $dev = User::factory()->create();
    $dev->assignRole('DEVELOPER');
    $this->actingAs($dev)->get('/finance')->assertForbidden();
    $this->actingAs($dev)->get('/finance/report')->assertForbidden();
});
