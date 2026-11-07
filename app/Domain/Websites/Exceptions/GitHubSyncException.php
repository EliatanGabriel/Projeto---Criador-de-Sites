<?php

namespace App\Domain\Websites\Exceptions;

use Exception;

class GitHubSyncException extends Exception
{
    public const UNSUPPORTED = 'unsupported';
    public const NOT_FOUND = 'not_found';
    public const UNAUTHORIZED = 'unauthorized';
    public const RATE_LIMITED = 'rate_limited';
    public const TIMEOUT = 'timeout';
    public const NETWORK_ERROR = 'network_error';
    public const API_ERROR = 'api_error';

    public function __construct(public readonly string $syncCode, string $message)
    {
        parent::__construct($message);
    }
}
