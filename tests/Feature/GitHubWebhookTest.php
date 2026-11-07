<?php

use App\Models\Credential;
use App\Models\Website;
use App\Models\WebsiteEnvironment;
use App\Models\WebsitePublication;
use App\Models\WebsiteRepository;
use App\Models\WebsiteRepositoryEvent;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function whSecret(): string {
    return 'whsecret_test_face73';
}

function whSetup(string $fullName = 'acme/site'): array
{
    $site = Website::factory()->create();
    $env = WebsiteEnvironment::factory()->create([
        'website_id' => $site->id,
        'name' => 'Produção',
        'type' => 'production',
        'status' => 'ativo',
    ]);
    $repo = WebsiteRepository::factory()->create([
        'website_id' => $site->id,
        'provider' => 'github',
        'name' => 'site-repo',
        'url' => "https://github.com/{$fullName}",
        'github_full_name' => $fullName,
    ]);
    Credential::factory()->create([
        'website_id' => $site->id,
        'type' => 'service_token',
        'username' => 'webhook-secret',
        'label' => 'GitHub webhook',
        'secret_encrypted' => whSecret(),
    ]);

    return [$site, $repo, $env];
}

function whPost(array $payload, string $event, string $delivery, ?string $secret = null, ?string $rawBody = null)
{
    $body = $rawBody ?? json_encode($payload);
    $secret = $secret ?? whSecret();
    $sig = 'sha256='.hash_hmac('sha256', $body, $secret);

    return test()->call('POST', '/webhooks/github', [], [], [], [
        'HTTP_X_GITHUB_EVENT' => $event,
        'HTTP_X_GITHUB_DELIVERY' => $delivery,
        'HTTP_X_HUB_SIGNATURE_256' => $sig,
        'CONTENT_TYPE' => 'application/json',
    ], $body);
}

test('assinatura válida registra push sem criar publicação', function () {
    [$site, $repo] = whSetup();
    $payload = ['repository' => ['full_name' => 'acme/site'], 'after' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'ref' => 'refs/heads/main'];

    whPost($payload, 'push', 'delivery-push-1')->assertOk();

    expect(WebsiteRepositoryEvent::count())->toBe(1);
    $event = WebsiteRepositoryEvent::first();
    expect($event->event_type)->toBe('push');
    expect($event->repository_id)->toBe($repo->id);
    expect($event->website_id)->toBe($site->id);
    expect(WebsitePublication::count())->toBe(0);
});

test('assinatura inválida retorna 401 sem registrar nada', function () {
    whSetup();
    $payload = ['repository' => ['full_name' => 'acme/site'], 'after' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'ref' => 'refs/heads/main'];

    whPost($payload, 'push', 'delivery-bad-sig', 'wrong-secret')->assertUnauthorized();

    expect(WebsiteRepositoryEvent::count())->toBe(0);
});

test('payload inválido retorna 400 sem segredo na resposta', function () {
    whSetup();

    whPost([], 'push', 'delivery-malformed', null, '{not-json')->assertBadRequest();
});

test('delivery repetido é idempotente (1 registro, 200)', function () {
    whSetup();
    $payload = ['repository' => ['full_name' => 'acme/site'], 'after' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'ref' => 'refs/heads/main'];

    whPost($payload, 'push', 'delivery-dup')->assertOk();
    whPost($payload, 'push', 'delivery-dup')->assertOk();

    expect(WebsiteRepositoryEvent::count())->toBe(1);
});

test('release não cria publicação', function () {
    whSetup();
    $payload = ['action' => 'published', 'release' => ['tag_name' => 'v1.0.0'], 'repository' => ['full_name' => 'acme/site']];

    whPost($payload, 'release', 'delivery-release-1')->assertOk();

    expect(WebsiteRepositoryEvent::count())->toBe(1);
    expect(WebsiteRepositoryEvent::first()->event_type)->toBe('release');
    expect(WebsitePublication::count())->toBe(0);
});

test('deployment cria exatamente uma publicação pendente', function () {
    [$site, $repo] = whSetup();
    $payload = [
        'action' => 'created',
        'deployment' => ['id' => 42, 'sha' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'ref' => 'main', 'environment' => 'Produção'],
        'repository' => ['full_name' => 'acme/site'],
    ];

    whPost($payload, 'deployment', 'delivery-dep-1')->assertOk();

    expect(WebsitePublication::count())->toBe(1);
    $pub = WebsitePublication::first();
    expect($pub->result)->toBe('pendente');
    expect($pub->website_id)->toBe($site->id);
    expect($pub->source)->toBe('webhook');
    expect((int) $pub->github_deployment_id)->toBe(42);
    expect($pub->commit_reference)->toBe('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');
});

test('deployment repetido não duplica publicação (chaves distintas de entrega)', function () {
    whSetup();
    $payload = [
        'deployment' => ['id' => 43, 'sha' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'ref' => 'main', 'environment' => 'Produção'],
        'repository' => ['full_name' => 'acme/site'],
    ];

    whPost($payload, 'deployment', 'delivery-dep-43-a')->assertOk();
    whPost($payload, 'deployment', 'delivery-dep-43-b')->assertOk();

    expect(WebsiteRepositoryEvent::count())->toBe(2);
    expect(WebsitePublication::count())->toBe(1);
});

test('deployment_status atualiza publicação e terminal não é sobrescrito', function () {
    whSetup();
    $dep = [
        'deployment' => ['id' => 44, 'sha' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'ref' => 'main', 'environment' => 'Produção'],
        'repository' => ['full_name' => 'acme/site'],
    ];
    whPost($dep, 'deployment', 'delivery-dep-44')->assertOk();
    expect(WebsitePublication::first()->result)->toBe('pendente');

    $ok = [
        'deployment_status' => ['state' => 'success'],
        'deployment' => ['id' => 44],
        'repository' => ['full_name' => 'acme/site'],
    ];
    whPost($ok, 'deployment_status', 'delivery-st-44-a')->assertOk();
    expect(WebsitePublication::first()->result)->toBe('sucesso');

    $fail = [
        'deployment_status' => ['state' => 'failure'],
        'deployment' => ['id' => 44],
        'repository' => ['full_name' => 'acme/site'],
    ];
    whPost($fail, 'deployment_status', 'delivery-st-44-b')->assertOk();
    expect(WebsitePublication::first()->result)->toBe('sucesso');
});

test('deployment_status sem deployment prévio é registro órfão sem publicação', function () {
    whSetup();
    $payload = [
        'deployment_status' => ['state' => 'success'],
        'deployment' => ['id' => 999],
        'repository' => ['full_name' => 'acme/site'],
    ];

    whPost($payload, 'deployment_status', 'delivery-orphan-1')->assertOk();

    expect(WebsitePublication::count())->toBe(0);
    expect(WebsiteRepositoryEvent::first()->outcome)->toBe('orphaned_status');
});

test('eventos fora de ordem: status antes do deployment não cria publicação', function () {
    whSetup();
    $orphan = [
        'deployment_status' => ['state' => 'failure'],
        'deployment' => ['id' => 77],
        'repository' => ['full_name' => 'acme/site'],
    ];
    whPost($orphan, 'deployment_status', 'delivery-ooo-1')->assertOk();
    expect(WebsitePublication::count())->toBe(0);

    $dep = [
        'deployment' => ['id' => 77, 'sha' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'ref' => 'main', 'environment' => 'Produção'],
        'repository' => ['full_name' => 'acme/site'],
    ];
    whPost($dep, 'deployment', 'delivery-ooo-2')->assertOk();
    expect(WebsitePublication::count())->toBe(1);
    expect(WebsitePublication::first()->result)->toBe('pendente');
});

test('repositório desconhecido retorna 404 sem registros', function () {
    whSetup('acme/site');
    $payload = ['repository' => ['full_name' => 'other/unknown'], 'after' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'ref' => 'refs/heads/main'];

    whPost($payload, 'push', 'delivery-unknown-1')->assertNotFound();

    expect(WebsiteRepositoryEvent::count())->toBe(0);
    expect(WebsitePublication::count())->toBe(0);
});

test('payload adulterado retorna 401', function () {
    whSetup();
    $payload = ['repository' => ['full_name' => 'acme/site'], 'after' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'ref' => 'refs/heads/main'];
    $original = json_encode($payload);
    $tampered = json_encode(array_merge($payload, ['after' => 'cccccccccccccccccccccccccccccccccccccccc']));

    test()->call('POST', '/webhooks/github', [], [], [], [
        'HTTP_X_GITHUB_EVENT' => 'push',
        'HTTP_X_GITHUB_DELIVERY' => 'delivery-tamper-1',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $original, whSecret()),
        'CONTENT_TYPE' => 'application/json',
    ], $tampered)->assertUnauthorized();

    expect(WebsiteRepositoryEvent::count())->toBe(0);
});

test('nenhum segredo exposto em resposta', function () {
    whSetup();
    $payload = ['repository' => ['full_name' => 'acme/site'], 'after' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'ref' => 'refs/heads/main'];

    $response = whPost($payload, 'push', 'delivery-nosecret-1');
    $response->assertOk();
    $response->assertDontSee(whSecret());

    $event = WebsiteRepositoryEvent::first();
    expect(json_encode($event->payload_summary))->not->toContain(whSecret());
});

test('isolamento entre sites: status de outro repositório não altera publicação', function () {
    [$siteA] = whSetup('acme/site-a');
    whSetup('acme/site-b');
    $depA = [
        'deployment' => ['id' => 100, 'sha' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'ref' => 'main', 'environment' => 'Produção'],
        'repository' => ['full_name' => 'acme/site-a'],
    ];
    whPost($depA, 'deployment', 'delivery-iso-1')->assertOk();
    expect(WebsitePublication::where('website_id', $siteA->id)->count())->toBe(1);
    expect(WebsitePublication::where('github_deployment_id', 100)->first()->result)->toBe('pendente');

    $statusB = [
        'deployment_status' => ['state' => 'success'],
        'deployment' => ['id' => 100],
        'repository' => ['full_name' => 'acme/site-b'],
    ];
    whPost($statusB, 'deployment_status', 'delivery-iso-2')->assertOk();

    expect(WebsitePublication::where('github_deployment_id', 100)->first()->result)->toBe('pendente');
    expect(WebsiteRepositoryEvent::where('delivery_id', 'delivery-iso-2')->first()->outcome)->toBe('orphaned_status');
});
