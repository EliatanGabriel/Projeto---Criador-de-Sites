<?php

namespace Tests\Unit;

use App\Domain\Websites\Services\SslHealthChecker;
use Carbon\Carbon;

class SslHealthCheckerTest extends \Tests\TestCase
{
    public function test_valid_certificate(): void
    {
        $checker = new SslHealthChecker(function (string $target, string $host, bool $verify) {
            $this->assertTrue($verify);
            return ['expires' => Carbon::now()->addDays(40)->toDateTimeString()];
        });

        $result = $checker->check('example.com', '93.184.216.34');
        $this->assertTrue($result['ssl_valid']);
        $this->assertNull($result['error_code']);
    }

    public function test_expired_certificate_is_classified_expired(): void
    {
        $calls = 0;
        $checker = new SslHealthChecker(function (string $target, string $host, bool $verify) use (&$calls) {
            $calls++;
            if ($verify) {
                return null;
            }
            $this->assertFalse($verify);
            return ['expires' => Carbon::now()->subDays(2)->toDateTimeString()];
        });

        $result = $checker->check('example.com', '93.184.216.34');
        $this->assertFalse($result['ssl_valid']);
        $this->assertEquals('ssl_expired', $result['error_code']);
        $this->assertEquals(2, $calls);
    }

    public function test_invalid_certificate_without_expiry_is_invalid(): void
    {
        $checker = new SslHealthChecker(function () {
            return null;
        });

        $result = $checker->check('example.com', '93.184.216.34');
        $this->assertFalse($result['ssl_valid']);
        $this->assertEquals('ssl_invalid', $result['error_code']);
    }

    public function test_http_only_domain_has_no_ssl_result(): void
    {
        $checker = new SslHealthChecker(function () {
            return null;
        });

        $result = $checker->check('example.com', '93.184.216.34');
        $this->assertFalse($result['ssl_valid']);
        $this->assertEquals('ssl_invalid', $result['error_code']);

    }

}
