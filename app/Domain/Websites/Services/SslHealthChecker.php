<?php

namespace App\Domain\Websites\Services;

use Carbon\Carbon;

class SslHealthChecker
{
    private $probe;

    public function __construct(?callable $probe = null)
    {
        $this->probe = $probe ?? fn (string $target, string $host, bool $verify) => $this->realProbe($target, $host, $verify);
    }

    public function check(string $host, string $validatedIp): array
    {
        $target = 'ssl://'.$validatedIp.':443';

        $verified = ($this->probe)($target, $host, true);

        if ($verified === null) {
            $unverified = ($this->probe)($target, $host, false);

            if ($unverified === null) {
                return ['ssl_valid' => false, 'ssl_expires_at' => null, 'error_code' => 'ssl_invalid'];
            }

            $expires = $unverified['expires'] ?? null;

            if ($expires ? Carbon::parse($expires)->isPast() : false) {
                return ['ssl_valid' => false, 'ssl_expires_at' => $expires, 'error_code' => 'ssl_expired'];
            }

            return ['ssl_valid' => false, 'ssl_expires_at' => $expires, 'error_code' => 'ssl_invalid'];
        }

        $expires = $verified['expires'] ?? null;

        if ($expires ? Carbon::parse($expires)->isPast() : false) {
            return ['ssl_valid' => false, 'ssl_expires_at' => $expires, 'error_code' => 'ssl_expired'];
        }

        return ['ssl_valid' => true, 'ssl_expires_at' => $expires, 'error_code' => null];
    }

    protected function realProbe(string $target, string $host, bool $verify): ?array
    {
        $context = stream_context_create_from_array(['ssl' => [
            'SNI_enabled' => true,
            'peer_name' => $host,
            'allow_self_signed' => false,
            'verify_peer' => $verify,
            'verify_peer_name' => $verify,
            'disable_compression' => true,
        ]]);

        if ($context === false) {
            return null;
        }

        $errno = 0;
        $errstr = '';

        $fp = @stream_socket_client($target, $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);

        if ($fp === false) {
            return null;
        }

        $cert = stream_socket_get_peer_certificate($fp, true);
        $info = $cert ? openssl_x509_parse($cert) : false;

        fclose($fp);

        if ($info === false) {
            return null;
        }

        return ['expires' => Carbon::createFromTimestamp($info['validTo_time_t'])->toDateTimeString()];
    }
}
