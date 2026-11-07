<?php

namespace App\Domain\Websites\Services;

use App\Domain\Websites\Exceptions\GitHubSyncException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GitHubClient
{
    private const API_BASE = 'https://api.github.com';

    private const TIMEOUT_SECONDS = 10;

    private const CONNECT_TIMEOUT_SECONDS = 5;

    public function parseFullName(?string $provider, ?string $url): ?string
    {
        if (strtolower((string) $provider) !== 'github' || !$url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $user = parse_url($url, PHP_URL_USER);

        if (strtolower((string) $host) !== 'github.com' || $user) {
            return null;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $path = preg_replace('#\.git$#', '', $path) ?? '';
        $segments = explode('/', $path);

        if (count($segments) !== 2 || !$segments[0] || !$segments[1]) {
            return null;
        }

        foreach ($segments as $segment) {
            if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', $segment)) {
                return null;
            }
        }

        return $segments[0].'/'.$segments[1];
    }

    public function fetchRepository(string $fullName, string $token): array
    {
        $data = $this->get('/repos/'.$this->escapeFullName($fullName), $token);

        $full = $data['full_name'] ?? $fullName;
        $branch = $data['default_branch'] ?? null;

        if (! is_string($full) || ! is_string($branch) || $branch === '') {
            throw new GitHubSyncException(GitHubSyncException::API_ERROR, 'Resposta do GitHub incompleta.');
        }

        return ['full_name' => $full, 'default_branch' => $branch];
    }

    public function fetchHeadCommit(string $fullName, string $branch, string $token): array
    {
        $data = $this->get('/repos/'.$this->escapeFullName($fullName).'/commits', $token, [
            'per_page' => 1,
            'sha' => $branch,
        ]);

        $commit = $data[0] ?? null;
        $sha = $commit['sha'] ?? null;
        $date = $commit['commit']['committer']['date'] ?? ($commit['commit']['author']['date'] ?? null);

        if (! is_string($sha) || $sha === '' || ! is_string($date) || $date === '') {
            throw new GitHubSyncException(GitHubSyncException::API_ERROR, 'Resposta do GitHub incompleta.');
        }

        return ['sha' => $sha, 'date' => $date];
    }

    public function countOpenPullRequests(string $fullName, string $token): int
    {
        $data = $this->get('/repos/'.$this->escapeFullName($fullName).'/pulls', $token, [
            'state' => 'open',
            'per_page' => 100,
        ]);

        if (! is_array($data) || ! array_is_list($data)) {
            throw new GitHubSyncException(GitHubSyncException::API_ERROR, 'Resposta do GitHub incompleta.');
        }

        return count($data);
    }

    protected function get(string $path, string $token, array $query = []): mixed
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/vnd.github+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                    'User-Agent' => 'WebStudioOS',
                ])
                ->acceptJson()
                ->get(self::API_BASE.$path, $query);
        } catch (ConnectionException $e) {
            if (str_contains(strtolower($e->getMessage()), 'timed out') || str_contains(strtolower($e->getMessage()), 'timeout')) {
                throw new GitHubSyncException(GitHubSyncException::TIMEOUT, 'Tempo esgotado ao contatar o GitHub.');
            }

            throw new GitHubSyncException(GitHubSyncException::NETWORK_ERROR, 'Falha de rede ao contatar o GitHub.');
        }

        if ($response->successful()) {
            return $response->json();
        }

        $status = $response->status();
        $message = (string) ($response->json('message') ?? '');

        if ($status === 404) {
            throw new GitHubSyncException(GitHubSyncException::NOT_FOUND, 'Repositório não encontrado no GitHub.');
        }

        if ($status === 401) {
            throw new GitHubSyncException(GitHubSyncException::UNAUTHORIZED, 'Token do GitHub inválido ou sem acesso.');
        }

        if ($status === 403 && (str_contains(strtolower($message), 'rate limit') || $response->header('X-RateLimit-Remaining') === '0')) {
            throw new GitHubSyncException(GitHubSyncException::RATE_LIMITED, 'Limite de requisições do GitHub atingido. Tente novamente mais tarde.');
        }

        if ($status === 403 || $status === 429) {
            throw new GitHubSyncException(GitHubSyncException::UNAUTHORIZED, 'Token do GitHub inválido ou sem acesso.');
        }

        throw new GitHubSyncException(GitHubSyncException::API_ERROR, 'Resposta inesperada do GitHub.');
    }

    protected function escapeFullName(string $fullName): string
    {
        $segments = explode('/', $fullName);

        if (count($segments) !== 2) {
            throw new GitHubSyncException(GitHubSyncException::UNSUPPORTED, 'Repositório com formato não suportado.');
        }

        return rawurlencode($segments[0]).'/'.rawurlencode($segments[1]);
    }
}
