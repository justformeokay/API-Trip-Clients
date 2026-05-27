<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when input validation fails.
 * Carries field-level error messages.
 */
final class ValidationException extends BaseException
{
    /** @var array<string, string[]> */
    private array $errors;

    /**
     * @param array<string, string[]> $errors
     */
    public function __construct(array $errors, string $message = 'Validation failed.')
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
    }

    /** @return array<string, string[]> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
