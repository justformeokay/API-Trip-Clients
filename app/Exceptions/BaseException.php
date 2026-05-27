<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Base exception for all application-specific exceptions.
 */
abstract class BaseException extends RuntimeException
{
    public function __construct(
        string           $message    = '',
        private readonly int $statusCode = 500,
        ?\Throwable      $previous   = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
