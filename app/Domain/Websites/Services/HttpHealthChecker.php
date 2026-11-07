<?php

namespace App\Domain\Websites\Services;

use App\Domain\Websites\Exceptions\SsrfBlockedException;

class HttpHealthChecker
{
    public const BODY_MAX_BYTES = 65536;

    public const HEADERS_MAX_BYTES = 131072;

    private const CONNECT_TIMEOUT = 5;

    private const TOTAL_TIMEOUT = 10;

    private const MAX_REDIRECTS = 3;

    private $runner;

    public function __construct(?callable $runner = null)
    {
        $this->runner = $runner ?? fn (array $hop, float $remaining) => $this->executeCurl($hop, $remaining);
    }

    public function check(string $url, SsrfGuard $guard, ?array $parsedUrl = null): array
    {
        $startedAt = microtime(true);
        $currentUrl = $url;
        $redirectsLeft = self::MAX_REDIRECTS;

        for ($hop = 0; ; $hop++) {
            $remaining = self::TOTAL_TIMEOUT - (microtime(true) - $startedAt);

            if ($remaining <= 0) {
                return $this->result(null, false, self::elapsed($startedAt), 'timeout', null);
            }

            try {
                $parsed = ($hop === 0 && $parsedUrl !== null) ? $parsedUrl : $guard->parseUrl($currentUrl);
            } catch (SsrfBlockedException $e) {
                if (str_contains($e->getMessage(), 'DNS')) {
                    return $this->result(null, false, self::elapsed($startedAt), 'dns_failed', null);
                }

                return $this->result(null, false, self::elapsed($startedAt), 'ssrf_blocked', null);
            }

            $ipsToTry = array_slice($parsed['ips'], 0, 3);
            $attemptResult = null;

            foreach ($ipsToTry as $ip) {
                $result = ($this->runner)([
                    'url' => $currentUrl,
                    'scheme' => $parsed['scheme'],
                    'host' => $parsed['host'],
                    'port' => $parsed['port'],
                    'ip' => $ip,
                ], $remaining);

                $effectiveIp = $result['effective_ip'] ?? null;

                if ($effectiveIp && $effectiveIp !== $ip) {
                    return $this->result(null, false, self::elapsed($startedAt), 'ssrf_mismatch', $effectiveIp);
                }

                if (empty($result['error']) && isset($result['status'])) {
                    $attemptResult = $result;
                    break;
                }

                $attemptResult = $result;
            }

            if ($attemptResult === null) {
                return $this->result(null, false, self::elapsed($startedAt), 'connection_failed', null);
            }

            if (! empty($attemptResult['error'])) {
                return $this->result(null, false, self::elapsed($startedAt), $attemptResult['error'], $attemptResult['effective_ip'] ?? null);
            }

            $status = (int) ($attemptResult['status'] ?? 0);
            $headers = $attemptResult['headers'] ?? [];

            if ($status >= 300 && $status < 400) {
                $location = $this->headerValue($headers, 'location');

                if ($location && $redirectsLeft > 0) {
                    $next = $this->resolveRedirect($currentUrl, $location);
                    try {
                        $guard->validateRedirect($next);
                    } catch (SsrfBlockedException $e) {
                        return $this->result($status, false, self::elapsed($startedAt), 'ssrf_blocked', $attemptResult['effective_ip'] ?? null);
                    }
                    $currentUrl = $next;
                    $redirectsLeft--;
                    continue;
                }

                return $this->result($status, false, self::elapsed($startedAt), $status === 301 || $status === 302 ? 'http_unexpected' : 'http_unexpected', $attemptResult['effective_ip'] ?? null);
            }

            $ok = $status >= 200 && $status < 300;

            return $this->result($status, $ok, self::elapsed($startedAt), $ok ? null : ($status === 0 ? 'connection_failed' : 'http_unexpected'), $attemptResult['effective_ip'] ?? null);
        }
    }

    protected function result(?int $status, bool $ok, int $latencyMs, ?string $errorCode, ?string $effectiveIp): array
    {
        return [
            'http_status' => $status,
            'ok' => $ok,
            'latency_ms' => $latencyMs,
            'error_code' => $errorCode,
            'effective_ip' => $effectiveIp,
        ];
    }

    protected static function elapsed(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    protected function headerValue(array $headers, string $name): ?string
    {
        $needle = strtolower($name);

        foreach ($headers as $key => $value) {
            if (is_array($value)) {
                if (strtolower((string) $key) === $needle) {
                    return (string) ($value[0] ?? null) ?: null;
                }
                continue;
            }

            $pieces = explode(':', (string) $key . ':' . $value, 2);

            if (count($pieces) === 2 && strtolower(trim($pieces[0])) === $needle) {
                return trim($pieces[1]);
            }
        }

        return null;
    }

    protected function resolveRedirect(string $currentUrl, string $location): string
    {
        $target = trim($location);

        if (preg_match('#^https?://#i', $target)) {
            return $target;
        }

        $parts = parse_url($currentUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = $parts['port'] ?? null;
        $hostWithPort = $host . ($port ? ':'.$port : '');

        if (str_starts_with($target, '//')) {
            return $scheme.':'. $target;
        }

        if (str_starts_with($target, '/')) {
            return $scheme.'://'.$hostWithPort.$target;
        }

        $path = $parts['path'] ?? '/';
        $dir = substr($path, 0, strrpos($path, '/') + 1) ?: '/';

        return $scheme.'://'.$hostWithPort.$dir.ltrim($target, '/');
    }

    protected function executeCurl(array $hop, float $remaining): array
    {
        $curl = curl_init($hop['url']);
        $headers = [];
        $bodyBytes = 0;
        $headerBytes = 0;
        $abortedForSize = false;
        $total = (int) max(1, floor($remaining));

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_PROXY => '',
            CURLOPT_PROXYAUTH => CURLAUTH_NONE,
            CURLOPT_PROTOCOLS_STR => 'http,https',
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => $total,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$headerBytes, &$abortedForSize, &$headers) {
                $headerBytes += strlen($header);

                if ($headerBytes > self::HEADERS_MAX_BYTES) {
                    $abortedForSize = true;
                    return 0;
                }

                $trimmed = trim($header);

                if ($trimmed !== '' && str_contains($trimmed, ':')) {
                    [$name, $value] = array_map('trim', explode(':', $trimmed, 2));
                    $headers[strtolower($name)] = $value;
                }

                return strlen($header);
            },
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$bodyBytes, &$abortedForSize) {
                $bodyBytes += strlen($chunk);

                if ($bodyBytes > self::BODY_MAX_BYTES) {
                    $abortedForSize = true;
                    return 0;
                }

                return strlen($chunk);
            },
            CURLOPT_RESOLVE => [$hop['host'].':'.$hop['port'].':'.$hop['ip']],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE => str_contains($hop['ip'], ':') ? CURL_IPRESOLVE_V6 : CURL_IPRESOLVE_V4,
            CURLOPT_HTTPHEADER => ['User-Agent: WebStudioOS HealthCheck', 'Accept: */*'],
        ]);

        curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $primaryIp = curl_getinfo($curl, CURLINFO_PRIMARY_IP);
        curl_close($curl);

        if ($abortedForSize) {
            return ['status' => null, 'headers' => $headers, 'error' => 'response_too_large', 'effective_ip' => $primaryIp ?: null];
        }

        if ($errno !== 0) {
            $lower = strtolower($error);

            if ($errno === CURLE_OPERATION_TIMEDOUT || str_contains($lower, 'timed out') || str_contains($lower, 'timeout')) {
                return ['status' => null, 'headers' => $headers, 'error' => 'timeout', 'effective_ip' => $primaryIp ?: null];
            }

            if ($errno === CURLE_COULDNT_RESOLVE_HOST || str_contains($lower, 'could not resolve')) {
                return ['status' => null, 'headers' => $headers, 'error' => 'dns_failed', 'effective_ip' => $primaryIp ?: null];
            }

            if ($errno === CURLE_PEER_FAILED_VERIFICATION || str_contains($lower, 'certificate')) {
                return ['status' => null, 'headers' => $headers, 'error' => 'ssl_invalid', 'effective_ip' => $primaryIp ?: null];
            }

            return ['status' => null, 'headers' => $headers, 'error' => 'connection_failed', 'effective_ip' => $primaryIp ?: null];
        }

        return ['status' => $status, 'headers' => $headers, 'error' => null, 'effective_ip' => $primaryIp ?: null];
    }
}
