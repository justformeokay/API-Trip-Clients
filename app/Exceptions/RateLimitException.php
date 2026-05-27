<?php

declare(strict_types=1);

namespace App\Exceptions;

final class RateLimitException extends BaseException
{
    public function __construct(string $message = 'Too many requests. Please slow down.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 429, $previous);
    }
}
