<?php

namespace App\Domain\Websites\Actions;

use App\Domain\Websites\Exceptions\GitHubSyncException;
use App\Domain\Websites\Services\GitHubClient;
use App\Models\WebsiteRepository;
use Illuminate\Support\Facades\DB;

class SyncWebsiteRepositoryAction
{
    public function __construct(private readonly GitHubClient $github = new GitHubClient()) {}

    public function __invoke(WebsiteRepository $repository): WebsiteRepository
    {
        return DB::transaction(function () use ($repository) {
            $locked = WebsiteRepository::query()->lockForUpdate()->findOrFail($repository->id);
            $website = $locked->website;

            if (! $website) {
                abort(404);
            }

            $fullName = $this->github->parseFullName($locked->provider, $locked->url);

            if (! $fullName) {
                return $this->markError($locked, GitHubSyncException::UNSUPPORTED, 'Repositório não é do GitHub ou possui URL inválida.');
            }

            $token = $this->resolveToken($website->id);

            if (!$token) {
                return $this->markError($locked, GitHubSyncException::UNAUTHORIZED, 'Token do GitHub não cadastrado para este site.');
            }

            try {
                $repo = $this->github->fetchRepository($fullName, $token);
                $branch = $repo['default_branch'];
                $commit = $this->github->fetchHeadCommit($repo['full_name'], $branch, $token);
                $openPrs = $this->github->countOpenPullRequests($repo['full_name'], $token);
            } catch (GitHubSyncException $e) {
                return $this->markError($locked, $e->syncCode, $e->getMessage());
            } finally {
                $token = null;
            }

            $locked->forceFill([
                'github_full_name' => $repo['full_name'],
                'default_branch' => $branch,
                'last_commit_sha' => $commit['sha'],
                'last_commit_at' => now()->parse($commit['date'])->toDateTimeString(),
                'open_pull_requests_count' => $openPrs,
                'last_synced_at' => now(),
                'last_sync_attempt_at' => now(),
                'last_sync_status' => 'ok',
                'last_sync_error' => null,
            ])->save();

            return $locked->refresh();
        });
    }

    protected function resolveToken(string $websiteId): ?string
    {
        $credential = \App\Models\Credential::query()
            ->where('website_id', $websiteId)
            ->where('type', 'service_token')
            ->where(function ($q) {
                $q->whereNull('username')->orWhere('username', '!=', 'webhook-secret');
            })
            ->orderByDesc('created_at')
            ->first();

        if (! $credential) {
            return null;
        }

        $secret = $credential->secret_encrypted;

        return $secret ? (string) $secret : null;
    }

    protected function markError(WebsiteRepository $repository, string $status, string $message): WebsiteRepository
    {
        $repository->forceFill([
            'last_sync_attempt_at' => now(),
            'last_sync_status' => $status,
            'last_sync_error' => $message,
        ])->save();

        return $repository->refresh();
    }
}
