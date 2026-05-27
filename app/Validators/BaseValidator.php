<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * Base validator with common validation rules.
 * All validators extend this class.
 */
abstract class BaseValidator
{
    /** @var array<string, string[]> */
    protected array $errors = [];

    /**
     * Validate $data against $rules and throw on failure.
     *
     * @param array<string, string> $rules  e.g. ['email' => 'required|email|max:255']
     * @throws ValidationException
     */
    public function validate(array $data, array $rules): void
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $value      = $data[$field] ?? null;
            $ruleList   = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }
    }

    // -------------------------------------------------------------------------
    // Rule engine
    // -------------------------------------------------------------------------

    private function applyRule(string $field, mixed $value, string $rule, array $data): void
    {
        [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

        match ($ruleName) {
            'required'  => $this->ruleRequired($field, $value),
            'string'    => $this->ruleString($field, $value),
            'email'     => $this->ruleEmail($field, $value),
            'min'       => $this->ruleMin($field, $value, (int) $param),
            'max'       => $this->ruleMax($field, $value, (int) $param),
            'numeric'   => $this->ruleNumeric($field, $value),
            'integer'   => $this->ruleInteger($field, $value),
            'boolean'   => $this->ruleBoolean($field, $value),
            'in'        => $this->ruleIn($field, $value, explode(',', $param ?? '')),
            'date'      => $this->ruleDate($field, $value),
            'url'       => $this->ruleUrl($field, $value),
            'nullable'  => null, // Allows null — just skip further rules if null
            'confirmed' => $this->ruleConfirmed($field, $value, $data),
            'latitude'  => $this->ruleLatitude($field, $value),
            'longitude' => $this->ruleLongitude($field, $value),
            'uuid'      => $this->ruleUuid($field, $value),
            default     => null,
        };
    }

    // -------------------------------------------------------------------------
    // Individual rules
    // -------------------------------------------------------------------------

    private function ruleRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, "The {$field} field is required.");
        }
    }

    private function ruleString(string $field, mixed $value): void
    {
        if ($value !== null && !is_string($value)) {
            $this->addError($field, "The {$field} must be a string.");
        }
    }

    private function ruleEmail(string $field, mixed $value): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$field} must be a valid email address.");
        }
    }

    private function ruleMin(string $field, mixed $value, int $min): void
    {
        if ($value === null) {
            return;
        }
        if (is_string($value) && mb_strlen($value) < $min) {
            $this->addError($field, "The {$field} must be at least {$min} characters.");
            return;
        }
        if (is_numeric($value) && (float) $value < $min) {
            $this->addError($field, "The {$field} must be at least {$min}.");
        }
    }

    private function ruleMax(string $field, mixed $value, int $max): void
    {
        if ($value === null) {
            return;
        }
        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, "The {$field} may not be greater than {$max} characters.");
            return;
        }
        if (is_numeric($value) && (float) $value > $max) {
            $this->addError($field, "The {$field} may not be greater than {$max}.");
        }
    }

    private function ruleNumeric(string $field, mixed $value): void
    {
        if ($value !== null && !is_numeric($value)) {
            $this->addError($field, "The {$field} must be a number.");
        }
    }

    private function ruleInteger(string $field, mixed $value): void
    {
        if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, "The {$field} must be an integer.");
        }
    }

    private function ruleBoolean(string $field, mixed $value): void
    {
        if ($value !== null && !in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true)) {
            $this->addError($field, "The {$field} must be true or false.");
        }
    }

    private function ruleIn(string $field, mixed $value, array $options): void
    {
        if ($value !== null && !in_array($value, $options, true)) {
            $this->addError($field, "The {$field} must be one of: " . implode(', ', $options) . '.');
        }
    }

    private function ruleDate(string $field, mixed $value): void
    {
        if ($value !== null) {
            $d = \DateTime::createFromFormat('Y-m-d', $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                $this->addError($field, "The {$field} must be a valid date (YYYY-MM-DD).");
            }
        }
    }

    private function ruleUrl(string $field, mixed $value): void
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "The {$field} must be a valid URL.");
        }
    }

    private function ruleConfirmed(string $field, mixed $value, array $data): void
    {
        $confirmField = $field . '_confirmation';
        if ($value !== ($data[$confirmField] ?? null)) {
            $this->addError($field, "The {$field} confirmation does not match.");
        }
    }

    private function ruleLatitude(string $field, mixed $value): void
    {
        if ($value !== null && (!is_numeric($value) || (float) $value < -90 || (float) $value > 90)) {
            $this->addError($field, "The {$field} must be a valid latitude (-90 to 90).");
        }
    }

    private function ruleLongitude(string $field, mixed $value): void
    {
        if ($value !== null && (!is_numeric($value) || (float) $value < -180 || (float) $value > 180)) {
            $this->addError($field, "The {$field} must be a valid longitude (-180 to 180).");
        }
    }

    private function ruleUuid(string $field, mixed $value): void
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        if ($value !== null && !preg_match($pattern, (string) $value)) {
            $this->addError($field, "The {$field} must be a valid UUID.");
        }
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
