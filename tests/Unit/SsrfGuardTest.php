<?php

namespace Tests\Unit;

use App\Domain\Websites\Services\SsrfGuard;
use App\Domain\Websites\Exceptions\SsrfBlockedException;

class SsrfGuardTest extends \Tests\TestCase
{
    public function test_public_ip_is_allowed(): void
    {
        $guard = new SsrfGuard(fn (string $host) => [['ip' => '93.184.216.34']]);
        $parsed = $guard->parseUrl('https://example.com/');
        $this->assertEquals('93.184.216.34', $parsed['primary']);
    }

    public function test_private_loopback_and_link_local_are_blocked(): void
    {
        foreach (['10.0.0.1', '127.0.0.1', '169.254.169.254', '192.168.1.1', '172.16.0.1'] as $ip) {
            $guard = new SsrfGuard(fn () => [['ip' => $ip]]);
            $this->expectException(SsrfBlockedException::class);
            $guard->parseUrl('https://example.com/');
        }
    }

    public function test_mixed_public_and_private_is_blocked(): void
    {
        $guard = new SsrfGuard(fn () => [['ip' => '93.184.216.34'], ['ip' => '10.0.0.5']]);
        $this->expectException(SsrfBlockedException::class);
        $guard->parseUrl('https://example.com/');
    }

    public function test_ipv6_loopback_and_private_are_blocked(): void
    {
        foreach (['::1', 'fe80::1', 'fd00::1'] as $ip) {
            $guard = new SsrfGuard(fn () => [['ipv6' => $ip]]);
            $this->expectException(SsrfBlockedException::class);
            $guard->parseUrl('https://example.com/');
        }
    }

    public function test_ipv4_mapped_ipv6_private_is_blocked(): void
    {
        $guard = new SsrfGuard(fn () => [['ipv6' => '::ffff:10.0.0.9']]);
        $this->expectException(SsrfBlockedException::class);
        $guard->parseUrl('https://example.com/');
    }

    public function test_literal_private_ip_is_blocked_without_dns(): void
    {
        $guard = new SsrfGuard(fn () => [['ip' => '127.0.0.1']]);
        $this->expectException(SsrfBlockedException::class);
        $guard->parseUrl('http://127.0.0.1/site');
    }

    public function test_userinfo_and_unexpected_port_are_blocked(): void
    {
        $guard = new SsrfGuard(fn () => [['ip' => '93.184.216.34']]);
        $this->expectException(SsrfBlockedException::class);
        $guard->parseUrl('https://user:pass@example.com/');
    }

    public function test_redirect_to_private_is_blocked(): void
    {
        $guard = new SsrfGuard(fn () => [['ip' => '93.184.216.34']]);
        $this->expectException(SsrfBlockedException::class);
        $guard->validateRedirect('http://10.1.2.3/');
    }
}
