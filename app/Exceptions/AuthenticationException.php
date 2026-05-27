<?php

declare(strict_types=1);

namespace App\Exceptions;

final class AuthenticationException extends BaseException
{
    public function __construct(string $message = 'Unauthenticated.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
