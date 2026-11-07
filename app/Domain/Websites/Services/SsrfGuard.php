<?php

namespace App\Domain\Websites\Services;

use App\Domain\Websites\Exceptions\SsrfBlockedException;

class SsrfGuard
{
    public const ALLOWED_PORTS = [80, 443];

    private $resolver;

    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver;
    }

    private const IPV4_NONGLOBAL_CIDRS = [
        ['0.0.0.0', 8],
        ['10.0.0.0', 8],
        ['100.64.0.0', 10],
        ['127.0.0.0', 8],
        ['169.254.0.0', 16],
        ['172.16.0.0', 12],
        ['192.0.0.0', 24],
        ['192.0.2.0', 24],
        ['192.88.99.0', 24],
        ['192.168.0.0', 16],
        ['198.18.0.0', 15],
        ['198.51.100.0', 24],
        ['203.0.113.0', 24],
        ['224.0.0.0', 4],
        ['240.0.0.0', 4],
    ];

    private const IPV6_NONGLOBAL_CIDRS = [
        ['::', 128],
        ['::1', 128],
        ['64:ff9b:1::', 48],
        ['100::', 64],
        ['2001::', 32],
        ['2001:db8::', 32],
        ['fc00::', 7],
        ['fe80::', 10],
        ['ff00::', 8],
    ];

    public function parseUrl(string $url): array
    {
        $url = trim($url);
        $parts = parse_url($url);

        if (! $parts || empty($parts['host'])) {
            throw new SsrfBlockedException('URL inválida.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new SsrfBlockedException('Esquema não suportado.');
        }

        if (! empty($parts['user']) || ! empty($parts['pass'])) {
            throw new SsrfBlockedException('Usuário/senha na URL.');
        }

        $host = mb_strtolower(trim((string) $parts['host']));

        if ($host === '') {
            throw new SsrfBlockedException('Host inválido.');
        }

        $isIpLiteral = $this->isIpLiteral($host);

        if (! $isIpLiteral && (preg_match('/^[0-9]+$/', $host) || preg_match('/^0[xX][0-9a-f]+$/', $host))) {
            throw new SsrfBlockedException('Representação ambígua de IP.');
        }

        if (! $isIpLiteral && ! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $host)) {
            throw new SsrfBlockedException('Host não suportado.');
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);

        if (! in_array($port, self::ALLOWED_PORTS, true)) {
            throw new SsrfBlockedException('Porta não permitida.');
        }

        $normalizedHost = $host;
        $hostForResolve = $normalizedHost;

        if ($this->isIpv6Literal($host)) {
            $hostForResolve = trim($host, '[]');
            $normalizedHost = $hostForResolve;
        }

        $ips = [];
        $literal = $this->extractLiteralIp($hostForResolve);

        if ($literal) {
            $ips = [$literal];
        } else {
            $ips = $this->resolveHost($hostForResolve);
        }

        $this->assertAllGlobal($ips);

        $ips = $this->normalizeIps($ips);

        if (empty($ips)) {
            throw new SsrfBlockedException('DNS indisponível.');
        }

        sort($ips, SORT_STRING);

        $primary = $ips[0];

        return [
            'scheme' => $scheme,
            'host' => $normalizedHost,
            'port' => $port,
            'ips' => $ips,
            'primary' => $primary,
            'host_for_resolve' => $hostForResolve,
        ];
    }

    public function validateRedirect(string $location): void
    {
        $parsed = parse_url($location);

        if (! $parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            throw new SsrfBlockedException('Redirect inválido.');
        }

        $this->parseUrl($location);
    }

    public function resolveHost(string $host): array
    {
        $ips = [];
        if ($this->resolver !== null) {
            $records = ($this->resolver)($host);
        } else {
            $records = dns_get_record($host, DNS_A + DNS_AAAA);
        }

        if (! $records) {
            throw new SsrfBlockedException('DNS indisponível.');
        }

        foreach ($records as $record) {
            if (! empty($record['ip'])) {
                $ips[] = $record['ip'];
            }
            if (! empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        $ips = array_unique($ips);

        if (empty($ips)) {
            throw new SsrfBlockedException('DNS indisponível.');
        }

        return array_values($ips);
    }

    public function assertAllGlobal(array $ips): void
    {
        foreach ($ips as $ip) {
            if ($this->isNonGlobal($ip)) {
                throw new SsrfBlockedException('Destino não global.');
            }
        }
    }

    public function isNonGlobal(string $ip): bool
    {
        $clean = trim($ip, "[]\n\t ");

        if ($this->isIpv6($clean)) {
            $mapped = $this->ipv4FromMappedIpv6($clean);
            if ($mapped) {
                return $this->isNonGlobal($mapped);
            }

            $bin = inet_pton($clean);

            if ($bin === false || strlen($bin) !== 16) {
                return true;
            }

            foreach (self::IPV6_NONGLOBAL_CIDRS as [$net, $prefix]) {
                $netBin = inet_pton($net);
                if ($netBin !== false && $this->ipv6InCidr($bin, $netBin, $prefix)) {
                    return true;
                }
            }

            return false;
        }

        if (! $this->isIpv4($clean)) {
            return true;
        }

        $ipLong = ip2long($clean);
        foreach (self::IPV4_NONGLOBAL_CIDRS as [$net, $prefix]) {
            $netLong = ip2long($net);
            if ($ipLong !== false && $netLong !== false && $this->ipv4InCidr($ipLong, $netLong, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function isIpLiteral(string $host): bool
    {
        return $this->isIpv4($host) || $this->isIpv6(trim($host, '[]'));
    }

    protected function extractLiteralIp(string $host): ?string
    {
        $clean = trim($host, '[]');

        if ($this->isIpv4($clean) || $this->isIpv6($clean)) {
            return $clean;
        }

        return null;
    }

    protected function isIpv6Literal(string $host): bool
    {
        return strlen($host) > 2 && str_starts_with($host, '[') && str_ends_with($host, ']') && $this->isIpv6(trim($host, '[]'));
    }

    protected function isIpv4(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    protected function isIpv6(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    protected function ipv4FromMappedIpv6(string $ip): ?string
    {
        $lower = mb_strtolower($ip);

        if (! str_starts_with($lower, '::ffff:')) {
            return null;
        }

        $mapped = substr($lower, 7);

        return $this->isIpv4($mapped) ? $mapped : null;
    }

    protected function normalizeIps(array $ips): array
    {
        $normalized = [];

        foreach ($ips as $ip) {
            $mapped = $this->ipv4FromMappedIpv6((string) $ip);
            $normalized[] = $mapped ?? $ip;
        }

        return array_values(array_unique($normalized));
    }

    protected function ipv4InCidr(int $ipLong, int $netLong, int $prefix): bool
    {
        if ($prefix === 0) {
            return true;
        }

        if ($prefix === 32) {
            return $ipLong === $netLong;
        }

        $mask = -1 << (32 - $prefix);

        return ($ipLong & $mask) === ($netLong & $mask);
    }

    protected function ipv6InCidr(string $ipBin, string $netBin, int $prefix): bool
    {
        $bytes = intdiv($prefix, 8);
        $bits = $prefix % 8;

        if ($bytes > 16) {
            return false;
        }

        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
            return false;
        }

        if ($bits === 0) {
            return true;
        }

        $mask = chr((0xff << (8 - $bits)) & 0xff);
        return (ord($ipBin[$bytes]) & ord($mask)) === (ord($netBin[$bytes]) & ord($mask));
    }
}
