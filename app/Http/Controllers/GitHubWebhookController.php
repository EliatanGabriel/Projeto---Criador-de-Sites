<?php

namespace App\Http\Controllers;

use App\Domain\Websites\Actions\HandleGitHubWebhookAction;
use App\Domain\Websites\Services\GitHubClient;
use App\Models\Credential;
use App\Models\WebsiteRepository;
use Illuminate\Http\Request;

class GitHubWebhookController extends Controller
{
    private const MAX_BODY_BYTES = 1048576;

    public function __invoke(Request $request, HandleGitHubWebhookAction $action, GitHubClient $github)
    {
        $rawBody = (string) $request->getContent();

        if (strlen($rawBody) > self::MAX_BODY_BYTES) {
            return response()->json(['status' => 'error'], 413)->header('Cache-Control', 'no-store, private');
        }

        $eventType = (string) $request->header('X-GitHub-Event');
        $deliveryId = (string) $request->header('X-GitHub-Delivery');
        $signature = (string) $request->header('X-Hub-Signature-256');

        if ($eventType === '' || $deliveryId === '' || $signature === '') {
            return response()->json(['status' => 'error'], 400)->header('Cache-Control', 'no-store, private');
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['status' => 'error'], 400)->header('Cache-Control', 'no-store, private');
        }

        $repository = $this->resolveRepository($payload, $github);

        if (! $repository) {
            return response()->json(['status' => 'error'], 404)->header('Cache-Control', 'no-store, private');
        }

        $secret = $this->resolveWebhookSecret((string) $repository->website_id);

        if (! $secret) {
            return response()->json(['status' => 'error'], 401)->header('Cache-Control', 'no-store, private');
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['status' => 'error'], 401)->header('Cache-Control', 'no-store, private');
        }

        $action($deliveryId, $eventType, $payload, $repository);

        return response()->json(['status' => 'accepted'], 200)->header('Cache-Control', 'no-store, private');
    }

    protected function resolveRepository(array $payload, GitHubClient $github): ?WebsiteRepository
    {
        $fullName = $payload['repository']['full_name'] ?? null;

        if (! is_string($fullName) || ! preg_match('#^[A-Za-z0-9][A-Za-z0-9_.-]*/[A-Za-z0-9][A-Za-z0-9_.-]*$#', $fullName)) {
            return null;
        }

        $repository = WebsiteRepository::query()
            ->where('github_full_name', $fullName)
            ->where('provider', 'github')
            ->first();

        if ($repository) {
            return $repository;
        }

        $candidates = WebsiteRepository::query()->where('provider', 'github')->get();

        foreach ($candidates as $candidate) {
            if ($github->parseFullName($candidate->provider, $candidate->url) === $fullName) {
                return $candidate;
            }
        }

        return null;
    }

    protected function resolveWebhookSecret(string $websiteId): ?string
    {
        $credential = Credential::query()
            ->where('website_id', $websiteId)
            ->where('type', 'service_token')
            ->where('username', 'webhook-secret')
            ->orderByDesc('created_at')
            ->first();

        if (! $credential) {
            return null;
        }

        $secret = $credential->secret_encrypted;

        return $secret ? (string) $secret : null;
    }
}
