<?php

declare(strict_types=1);

namespace Aphrodite\Exceptions;

/**
 * Exception for authentication failures.
 */
class AuthenticationException extends AphroditeException
{
    protected ?string $guard = null;

    public function __construct(
        string $message = 'Unauthenticated',
        ?string $guard = null,
        int $code = 401,
        array $context = []
    ) {
        $this->guard = $guard;
        parent::__construct($message, $code, null, $context);
    }

    /**
     * Get the guard that failed authentication.
     */
    public function getGuard(): ?string
    {
        return $this->guard;
    }

    /**
     * Create for a specific guard.
     */
    public static function forGuard(string $guard): self
    {
        return new self("Unauthenticated for guard: {$guard}", $guard);
    }
}
