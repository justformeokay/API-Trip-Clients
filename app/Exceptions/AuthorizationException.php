<?php

declare(strict_types=1);

namespace App\Exceptions;

final class AuthorizationException extends BaseException
{
    public function __construct(string $message = 'Forbidden.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
