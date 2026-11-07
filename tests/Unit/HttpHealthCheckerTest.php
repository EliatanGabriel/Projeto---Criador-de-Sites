<?php

namespace Tests\Unit;

use App\Domain\Websites\Services\HttpHealthChecker;
use App\Domain\Websites\Services\SsrfGuard;

class HttpHealthCheckerTest extends \Tests\TestCase
{
    public function test_effective_ip_must_match_validated(): void
    {
        $guard = new SsrfGuard(fn () => [['ip' => '93.184.216.34']]);
        $checker = new HttpHealthChecker(fn (array $hop) => [
            'status' => 200,
            'headers' => [],
            'error' => null,
            'effective_ip' => '10.0.0.9',
        ]);

        $result = $checker->check('https://example.com/', $guard);
        $this->assertEquals('ssrf_mismatch', $result['error_code']);
        $this->assertFalse($result['ok']);
    }

    public function test_public_ok_response_is_ok(): void
    {
        $guard = new SsrfGuard(fn () => [['ip' => '93.184.216.34']]);
        $checker = new HttpHealthChecker(fn (array $hop) => [
            'status' => 200,
            'headers' => [],
            'error' => null,
            'effective_ip' => '93.184.216.34',
        ]);

        $result = $checker->check('https://example.com/', $guard);
        $this->assertTrue($result['ok']);
        $this->assertEquals(200, $result['http_status']);
        $this->assertNull($result['error_code']);
    }

    public function test_redirect_followed_to_validated_target(): void
    {
        $guard = new SsrfGuard(fn (string $host) => $host === 'example.com' ? [['ip' => '93.184.216.34']] : [['ip' => '93.184.216.35']]);
        $calls = 0;
        $checker = new HttpHealthChecker(function (array $hop) use (&$calls) {
            $calls++;
            if ($calls === 1) {
                return ['status' => 301, 'headers' => ['location' => 'https://example.com/new'], 'error' => null, 'effective_ip' => '93.184.216.34'];
            }
            return ['status' => 200, 'headers' => [], 'error' => null, 'effective_ip' => '93.184.216.34'];
        });

        $result = $checker->check('https://example.com/old', $guard);
        $this->assertTrue($result['ok']);
        $this->assertEquals(2, $calls);
    }

    public function test_redirect_to_private_never_executes_second_hop(): void
    {
        $guard = new SsrfGuard(fn (string $host) => $host === 'example.com' ? [['ip' => '93.184.216.34']] : [['ip' => '10.0.0.1']]);
        $calls = 0;
        $checker = new HttpHealthChecker(function (array $hop) use (&$calls) {
            $calls++;
            return ['status' => 301, 'headers' => ['location' => 'http://10.0.0.1/admin'], 'error' => null, 'effective_ip' => '93.184.216.34'];
        });

        $result = $checker->check('https://example.com/', $guard);
        $this->assertEquals('ssrf_blocked', $result['error_code']);
        $this->assertEquals(1, $calls);
    }
}
