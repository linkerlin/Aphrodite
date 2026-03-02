<?php

declare(strict_types=1);

namespace Aphrodite\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all Aphrodite framework exceptions.
 */
class AphroditeException extends Exception
{
    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get exception context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set exception context.
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Add context value.
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Create exception with context.
     */
    public static function withContext(string $message, array $context = [], int $code = 0, ?Throwable $previous = null): self
    {
        return new self($message, $code, $previous, $context);
    }
}
