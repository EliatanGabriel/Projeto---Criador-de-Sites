<?php

namespace App\Domain\Websites\Actions;

use App\Models\WebsiteEnvironment;
use App\Models\WebsitePublication;
use App\Models\WebsiteRepositoryEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class HandleGitHubWebhookAction
{
    public function __invoke(string $deliveryId, string $eventType, array $payload, \App\Models\WebsiteRepository $repository): WebsiteRepositoryEvent
    {
        $eventType = $this->normalizeEventType($eventType, $payload);
        $summary = $this->summarize($payload, $eventType);
        $deploymentId = $this->extractDeploymentId($payload);

        $existing = WebsiteRepositoryEvent::query()->where('delivery_id', $deliveryId)->first();

        if ($existing) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($deliveryId, $eventType, $payload, $repository, $summary, $deploymentId) {
                $event = WebsiteRepositoryEvent::query()->create([
                    'repository_id' => $repository->id,
                    'website_id' => $repository->website_id,
                    'delivery_id' => $deliveryId,
                    'event_type' => $eventType,
                    'payload_summary' => $summary,
                    'github_deployment_id' => $deploymentId,
                    'commit_sha' => $this->extractSha($payload, $eventType),
                    'ref' => $this->extractRef($payload, $eventType),
                    'outcome' => 'accepted',
                ]);

                $outcome = $this->process($event, $payload, $repository);

                if ($outcome !== 'accepted') {
                    $event->forceFill(['outcome' => $outcome])->save();
                }

                return $event->refresh();
            });
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23505', '23000'], true)) {
                return WebsiteRepositoryEvent::query()->where('delivery_id', $deliveryId)->firstOrFail();
            }

            throw $e;
        }
    }

    protected function process(WebsiteRepositoryEvent $event, array $payload, \App\Models\WebsiteRepository $repository): string
    {
        if (in_array($event->event_type, ['push', 'release', 'ping'], true)) {
            return 'ignored';
        }

        if ($event->event_type === 'deployment') {
            return $this->processDeployment($event, $payload, $repository);
        }

        if ($event->event_type === 'deployment_status') {
            return $this->processDeploymentStatus($event, $payload, $repository);
        }

        return 'ignored';
    }

    protected function processDeployment(WebsiteRepositoryEvent $event, array $payload, \App\Models\WebsiteRepository $repository): string
    {
        $deploymentId = $event->github_deployment_id;

        if (! $deploymentId) {
            return 'ignored';
        }

        $existing = WebsitePublication::query()->where('github_deployment_id', $deploymentId)->first();

        if ($existing) {
            return 'accepted';
        }

        $environmentName = (string) ($payload['deployment']['environment'] ?? $payload['deployment_status']['deployment']['environment'] ?? $payload['deployment']['environment_name'] ?? '');
        $sha = (string) ($payload['deployment']['sha'] ?? '');
        $ref = (string) ($payload['deployment']['ref'] ?? '');
        $environment = $this->resolveEnvironment($repository->website_id, $environmentName);

        if (! $environment) {
            return 'ignored';
        }

        $publication = WebsitePublication::query()->create([
            'website_id' => $repository->website_id,
            'environment_id' => $environment->id,
            'version' => $ref !== '' ? $ref : null,
            'published_at' => now(),
            'responsible_id' => null,
            'description' => 'Implantação iniciada via GitHub webhook (#'.$deploymentId.').',
            'commit_reference' => $sha !== '' ? $sha : null,
            'result' => 'pendente',
            'source' => 'webhook',
            'github_deployment_id' => $deploymentId,
            'notes' => null,
        ]);

        return 'accepted';
    }

    protected function processDeploymentStatus(WebsiteRepositoryEvent $event, array $payload, \App\Models\WebsiteRepository $repository): string
    {
        $deploymentId = $event->github_deployment_id;
        $state = strtolower((string) ($payload['deployment_status']['state'] ?? ''));

        if (! $deploymentId || ! in_array($state, ['pending', 'queued', 'in_progress', 'success', 'failure', 'error', 'inactive'], true)) {
            return 'ignored';
        }

        $publication = WebsitePublication::query()
            ->where('github_deployment_id', $deploymentId)
            ->where('website_id', $repository->website_id)
            ->lockForUpdate()
            ->first();

        if (! $publication) {
            return 'orphaned_status';
        }

        if ($publication->result !== 'pendente') {
            return 'accepted';
        }

        $result = match ($state) {
            'success' => 'sucesso',
            'failure', 'error', 'inactive' => 'falha',
            default => null,
        };

        if (! $result) {
            return 'accepted';
        }

        $publication->forceFill(['result' => $result])->save();

        return 'accepted';
    }

    protected function resolveEnvironment(string $websiteId, string $name): ?WebsiteEnvironment
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $byName = WebsiteEnvironment::query()
            ->where('website_id', $websiteId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($byName) {
            return $byName;
        }

        $byType = WebsiteEnvironment::query()
            ->where('website_id', $websiteId)
            ->where('type', mb_strtolower($name))
            ->first();

        return $byType;
    }

    protected function normalizeEventType(string $eventType, array $payload): string
    {
        $type = strtolower(trim($eventType));

        if ($type === 'ping') {
            return 'ping';
        }

        if ($type === 'push') {
            return 'push';
        }

        if ($type === 'release') {
            return 'release';
        }

        if ($type === 'deployment') {
            return 'deployment';
        }

        if ($type === 'deployment_status') {
            return 'deployment_status';
        }

        return $type !== '' ? $type : 'ignored';
    }

    protected function extractDeploymentId(array $payload): ?int
    {
        $id = $payload['deployment']['id'] ?? $payload['deployment_status']['deployment']['id'] ?? null;

        return is_numeric($id) ? (int) $id : null;
    }

    protected function extractSha(array $payload, string $eventType): ?string
    {
        $sha = $payload['deployment']['sha'] ?? $payload['deployment_status']['deployment']['sha'] ?? $payload['after'] ?? $payload['head_commit']['id'] ?? null;

        return is_string($sha) && $sha !== '' ? $sha : null;
    }

    protected function extractRef(array $payload, string $eventType): ?string
    {
        if ($eventType === 'release') {
            $tag = $payload['release']['tag_name'] ?? null;

            return is_string($tag) && $tag !== '' ? $tag : null;
        }

        $ref = $payload['deployment']['ref'] ?? $payload['deployment_status']['deployment']['ref'] ?? $payload['ref'] ?? null;

        return is_string($ref) && $ref !== '' ? $ref : null;
    }

    protected function summarize(array $payload, string $eventType): array
    {
        return [
            'repository' => $payload['repository']['full_name'] ?? null,
            'deployment_id' => $this->extractDeploymentId($payload),
            'sha' => $this->extractSha($payload, $eventType),
            'ref' => $this->extractRef($payload, $eventType),
            'environment' => $payload['deployment']['environment'] ?? $payload['deployment_status']['deployment']['environment'] ?? null,
            'status_state' => $payload['deployment_status']['state'] ?? null,
            'action' => $payload['action'] ?? null,
        ];
    }
}
