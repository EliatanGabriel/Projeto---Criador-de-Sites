<?php

namespace App\Domain\Websites\Exceptions;

use Exception;

class SsrfBlockedException extends Exception
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
