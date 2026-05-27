<?php

declare(strict_types=1);

namespace App\Exceptions;

final class MethodNotAllowedException extends BaseException
{
    public function __construct(string $message = 'Method not allowed.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 405, $previous);
    }
}
