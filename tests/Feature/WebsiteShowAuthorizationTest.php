<?php

use App\Models\Website;
use App\Models\WebsiteRepository;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function sOwner()
{
    $u = \App\Models\User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}

function sCommercial()
{
    $u = \App\Models\User::factory()->create();
    $u->assignRole('COMMERCIAL');
    return $u;
}

function sDeveloper()
{
    $u = \App\Models\User::factory()->create();
    $u->assignRole('DEVELOPER');
    return $u;
}

test('OWNER acessa sites de diferentes clientes', function () {
    $owner = sOwner();
    $siteA = Website::factory()->create(['client_id' => \App\Models\Client::factory()->create()->id]);
    $siteB = Website::factory()->create(['client_id' => \App\Models\Client::factory()->create()->id]);

    $this->actingAs($owner)->get("/sites/{$siteA->id}")->assertOk();
    $this->actingAs($owner)->get("/sites/{$siteB->id}")->assertOk();
});

test('COMMERCIAL acessa site com oportunidade no escopo', function () {
    $commercial = sCommercial();
    $client = \App\Models\Client::factory()->create();
    $site = Website::factory()->create(['client_id' => $client->id]);
    \App\Models\Opportunity::factory()->create(['client_id' => $client->id, 'responsible_id' => $commercial->id]);

    $this->actingAs($commercial)->get("/sites/{$site->id}")->assertOk();
});

test('COMMERCIAL acessa site com demanda no escopo', function () {
    $commercial = sCommercial();
    $client = \App\Models\Client::factory()->create();
    $site = Website::factory()->create(['client_id' => $client->id]);
    \App\Models\Demand::factory()->create(['client_id' => $client->id, 'commercial_owner_id' => $commercial->id]);

    $this->actingAs($commercial)->get("/sites/{$site->id}")->assertOk();
});

test('COMMERCIAL recebe 403 fora do escopo', function () {
    $commercial = sCommercial();
    $client = \App\Models\Client::factory()->create();
    $site = Website::factory()->create(['client_id' => $client->id]);

    $this->actingAs($commercial)->get("/sites/{$site->id}")->assertForbidden();
});

test('DEVELOPER acessa site vinculado a projeto onde é member', function () {
    $developer = sDeveloper();
    $client = \App\Models\Client::factory()->create();
    $project = \App\Models\Project::factory()->create(['client_id' => $client->id]);
    $project->members()->create(['user_id' => $developer->id, 'role' => 'developer']);
    $site = Website::factory()->create(['client_id' => $client->id]);
    $site->websiteProjects()->create(['project_id' => $project->id, 'relation_type' => 'evolution']);

    $this->actingAs($developer)->get("/sites/{$site->id}")->assertOk();
});

test('DEVELOPER acessa site onde é technical_owner do projeto', function () {
    $developer = sDeveloper();
    $client = \App\Models\Client::factory()->create();
    $project = \App\Models\Project::factory()->create(['client_id' => $client->id, 'technical_owner_id' => $developer->id]);
    $site = Website::factory()->create(['client_id' => $client->id]);
    $site->websiteProjects()->create(['project_id' => $project->id, 'relation_type' => 'origin']);

    $this->actingAs($developer)->get("/sites/{$site->id}")->assertOk();
});

test('DEVELOPER recebe 403 para site sem vínculo autorizado', function () {
    $developer = sDeveloper();
    $site = Website::factory()->create();

    $this->actingAs($developer)->get("/sites/{$site->id}")->assertForbidden();
});

test('usuário inativo não acessa a página', function () {
    $user = \App\Models\User::factory()->create();
    $user->assignRole('OWNER');
    $site = Website::factory()->create();

    $this->actingAs($user);
    $user->update(['is_active' => false]);

    $this->get("/sites/{$site->id}")->assertRedirect('/login');
    $this->assertGuest();
});

test('Sincronização GitHub continua funcionando para OWNER após a autorização', function () {
    $owner = sOwner();
    $site = Website::factory()->create();
    $repo = WebsiteRepository::factory()->create([
        'website_id' => $site->id,
        'provider' => 'github',
        'url' => 'https://github.com/acme/site',
        'default_branch' => 'main',
    ]);
    \App\Models\Credential::factory()->create([
        'website_id' => $site->id,
        'type' => 'service_token',
        'secret_encrypted' => 'ghp_showAuthz',
    ]);
    Http::fake([
        'api.github.com/repos/acme/site' => Http::response(['full_name' => 'acme/site', 'default_branch' => 'main']),
        'api.github.com/repos/acme/site/commits*' => Http::response([['sha' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'commit' => ['committer' => ['date' => '2026-11-07T12:00:00Z'], 'author' => ['date' => '2026-11-07T12:00:00Z']]]]),
        'api.github.com/repos/acme/site/pulls*' => Http::response([]),
    ]);

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    expect($repo->refresh()->last_sync_status)->toBe('ok');
});
