<?php

declare(strict_types=1);

namespace App\Exceptions;

final class DatabaseException extends BaseException
{
    public function __construct(string $message = 'Database error.', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
