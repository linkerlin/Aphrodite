<?php

declare(strict_types=1);

namespace Aphrodite\Exceptions;

/**
 * Exception for validation errors.
 */
class ValidationException extends AphroditeException
{
    /**
     * @var array<string, string[]>
     */
    protected array $errors = [];

    /**
     * @param array<string, string[]> $errors Field => messages mapping
     */
    public function __construct(
        array $errors = [],
        string $message = 'Validation failed',
        int $code = 422,
        array $context = []
    ) {
        parent::__construct($message, $code, null, $context);
        $this->errors = $errors;
    }

    /**
     * Get validation errors.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field.
     *
     * @return string[]
     */
    public function getErrorsForField(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if field has errors.
     */
    public function hasErrorsForField(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Get first error for a field.
     */
    public function getFirstErrorForField(string $field): ?string
    {
        $errors = $this->errors[$field] ?? [];
        return $errors[0] ?? null;
    }

    /**
     * Create from single field error.
     */
    public static function forField(string $field, string $message): self
    {
        return new self([$field => [$message]]);
    }

    /**
     * Create from multiple field errors.
     *
     * @param array<string, string[]> $errors
     */
    public static function fromErrors(array $errors): self
    {
        return new self($errors);
    }
}
