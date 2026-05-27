<?php

declare(strict_types=1);

namespace App\Exceptions;

final class NotFoundException extends BaseException
{
    public function __construct(string $message = 'Resource not found.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
