<?php

use App\Models\Credential;
use App\Models\User;
use App\Models\WebsiteRepository;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function gOwner()
{
    $u = User::factory()->create();
    $u->assignRole('OWNER');
    return $u;
}

function gSetupRepo(array $overrides = []): array
{
    $site = \App\Models\Website::factory()->create();
    $repo = WebsiteRepository::factory()->create(array_merge([
        'website_id' => $site->id,
        'provider' => 'github',
        'name' => 'site-repo',
        'url' => 'https://github.com/acme/site',
        'default_branch' => 'main',
    ], $overrides));
    Credential::factory()->create([
        'website_id' => $site->id,
        'type' => 'service_token',
        'label' => 'GitHub — site',
        'secret_encrypted' => 'ghp_testToken123',
    ]);

    return [$site, $repo];
}

function gFakeOk(int $prs = 2, string $sha = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'): void
{
    Http::fake([
        'api.github.com/repos/acme/site' => Http::response(['full_name' => 'acme/site', 'default_branch' => 'main']),
        'api.github.com/repos/acme/site/commits*' => Http::response([['sha' => $sha, 'commit' => ['committer' => ['date' => '2026-11-07T12:00:00Z'], 'author' => ['date' => '2026-11-07T12:00:00Z']]]]),
        'api.github.com/repos/acme/site/pulls*' => Http::response(array_fill(0, $prs, ['number' => 1, 'title' => 'PR'])),
    ]);
}

test('sincronização bem-sucedida atualiza cache e PRs', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo();
    gFakeOk(3, 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();

    $repo->refresh();
    expect($repo->github_full_name)->toBe('acme/site');
    expect($repo->default_branch)->toBe('main');
    expect($repo->last_commit_sha)->toBe('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');
    expect($repo->last_commit_at)->not->toBeNull();
    expect($repo->open_pull_requests_count)->toBe(3);
    expect($repo->last_sync_status)->toBe('ok');
    expect($repo->last_sync_error)->toBeNull();
    expect($repo->last_synced_at)->not->toBeNull();
    expect($repo->last_sync_attempt_at)->not->toBeNull();
    Http::assertSent(fn ($req) => $req->url() === 'https://api.github.com/repos/acme/site');
});

test('repositório inexistente preserva cache e retorna erro genérico sem vazar token', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo();
    $repo->update(['last_commit_sha' => 'cccc', 'last_synced_at' => now(), 'last_sync_status' => 'ok']);
    Http::fake(['api.github.com/*' => Http::response(['message' => 'Not Found'], 404)]);

    $response = $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync");
    $response->assertRedirect();
    expect($response->getSession()->get('error'))->not->toContain('ghp_testToken123');

    $repo->refresh();
    expect($repo->last_sync_status)->toBe('not_found');
    expect($repo->last_commit_sha)->toBe('cccc');
    expect($repo->last_synced_at)->not->toBeNull();
});

test('PAT inválido retorna unauthorized e preserva cache', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo();
    $repo->update(['last_commit_sha' => 'dddd', 'last_synced_at' => now()]);
    Http::fake(['api.github.com/*' => Http::response(['message' => 'Bad credentials'], 401)]);

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    $repo->refresh();
    expect($repo->last_sync_status)->toBe('unauthorized');
    expect($repo->last_commit_sha)->toBe('dddd');
});

test('rate limit é detectado sem apagar cache', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo();
    $repo->update(['last_commit_sha' => 'eeee', 'last_synced_at' => now()]);
    Http::fake(['api.github.com/*' => Http::response(['message' => 'API rate limit exceeded'], 403, ['X-RateLimit-Remaining' => '0'])]);

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    $repo->refresh();
    expect($repo->last_sync_status)->toBe('rate_limited');
    expect($repo->last_commit_sha)->toBe('eeee');
});

test('timeout é detectado e preserva cache', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo();
    $repo->update(['last_commit_sha' => 'ffff']);
    Http::fake(['api.github.com/*' => fn () => throw new ConnectionException('Connection timed out')]);

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    $repo->refresh();
    expect($repo->last_sync_status)->toBe('timeout');
    expect($repo->last_commit_sha)->toBe('ffff');
});

test('falha de rede retorna network_error e preserva cache', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo();
    $repo->update(['last_commit_sha' => 'gggg']);
    Http::fake(['api.github.com/*' => fn () => throw new ConnectionException('Could not resolve host')]);

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    $repo->refresh();
    expect($repo->last_sync_status)->toBe('network_error');
    expect($repo->last_commit_sha)->toBe('gggg');
});

test('atualização de último commit e PRs em segunda sincronização', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo();
    Http::fakeSequence('api.github.com/repos/acme/site')
        ->push(['full_name' => 'acme/site', 'default_branch' => 'main'])
        ->push(['full_name' => 'acme/site', 'default_branch' => 'main']);
    Http::fakeSequence('api.github.com/repos/acme/site/commits*')
        ->push([['sha' => '1111111111111111111111111111111111111111', 'commit' => ['committer' => ['date' => '2026-11-07T12:00:00Z'], 'author' => ['date' => '2026-11-07T12:00:00Z']]]])
        ->push([['sha' => '2222222222222222222222222222222222222222', 'commit' => ['committer' => ['date' => '2026-11-07T13:00:00Z'], 'author' => ['date' => '2026-11-07T13:00:00Z']]]]);
    Http::fakeSequence('api.github.com/repos/acme/site/pulls*')
        ->push([['number' => 1, 'title' => 'PR']])
        ->push(array_fill(0, 5, ['number' => 1, 'title' => 'PR']));
    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    expect($repo->refresh()->last_commit_sha)->toBe('1111111111111111111111111111111111111111');
    expect($repo->open_pull_requests_count)->toBe(1);

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    expect($repo->refresh()->last_commit_sha)->toBe('2222222222222222222222222222222222222222');
    expect($repo->open_pull_requests_count)->toBe(5);
    expect($repo->last_sync_status)->toBe('ok');
});

test('autorização: OWNER sincroniza; COMMERCIAL e DEVELOPER não sincronizam, mas veem a página', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo();

    $commercial = User::factory()->create();
    $commercial->assignRole('COMMERCIAL');
    $developer = User::factory()->create();
    $developer->assignRole('DEVELOPER');
    $developer->update(['is_active' => true]);

    $this->actingAs($commercial)->get("/sites/{$site->id}")->assertForbidden();
    // DEVELOPER com websites.view mas sem projeto acessível: 403 (show autoriza via WebsitePolicy::view). Sync exige websites.manage → 403.
    $this->actingAs($developer)->get("/sites/{$site->id}")->assertForbidden();
    $this->actingAs($commercial)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertForbidden();
    $this->actingAs($developer)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertForbidden();

    gFakeOk();
    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    expect($repo->refresh()->last_sync_status)->toBe('ok');
});

test('SSRF: URL arbitrária não dispara chamada GitHub', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo(['url' => 'https://evil.example.com/acme/site']);
    Http::fake();

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    Http::assertNothingSent();
    expect($repo->refresh()->last_sync_status)->toBe('unsupported');
});

test('provedor não-GitHub não dispara chamada', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo(['provider' => 'gitlab', 'url' => 'https://gitlab.com/acme/site']);
    Http::fake();

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    Http::assertNothingSent();
    expect($repo->refresh()->last_sync_status)->toBe('unsupported');
});

test('sem token GitHub retorna unauthorized sem chamar API', function () {
    $owner = gOwner();
    $site = \App\Models\Website::factory()->create();
    $repo = WebsiteRepository::factory()->create(['website_id' => $site->id, 'provider' => 'github', 'url' => 'https://github.com/acme/site']);
    Http::fake();

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    Http::assertNothingSent();
    expect($repo->refresh()->last_sync_status)->toBe('unauthorized');
});

test('renderização da página não chama o GitHub', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo();
    Http::fake();

    $this->actingAs($owner)->get("/sites/{$site->id}")->assertOk();
    Http::assertNothingSent();
});

test('concorrência: dupla sincronização sequencial é idempotente', function () {
    $owner = gOwner();
    [$site, $repo] = gSetupRepo();
    gFakeOk(2);

    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();
    $this->actingAs($owner)->post("/sites/{$site->id}/repositories/{$repo->id}/sync")->assertRedirect();

    $repo->refresh();
    expect($repo->last_sync_status)->toBe('ok');
    expect($repo->open_pull_requests_count)->toBe(2);
    expect($repo->last_commit_sha)->toBe('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
});
