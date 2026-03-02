<?php

declare(strict_types=1);

namespace Aphrodite\Exceptions;

/**
 * Exception for authorization failures.
 */
class AuthorizationException extends AphroditeException
{
    protected ?string $ability = null;
    protected mixed $subject = null;

    public function __construct(
        string $message = 'Unauthorized',
        ?string $ability = null,
        mixed $subject = null,
        int $code = 403,
        array $context = []
    ) {
        $this->ability = $ability;
        $this->subject = $subject;

        if ($message === 'Unauthorized' && $ability !== null) {
            $message = "Not authorized to {$ability}";
        }

        parent::__construct($message, $code, null, $context);
    }

    /**
     * Get the ability that was denied.
     */
    public function getAbility(): ?string
    {
        return $this->ability;
    }

    /**
     * Get the subject of the authorization check.
     */
    public function getSubject(): mixed
    {
        return $this->subject;
    }

    /**
     * Create for a specific ability.
     */
    public static function forAbility(string $ability, mixed $subject = null): self
    {
        return new self("Not authorized to {$ability}", $ability, $subject);
    }
}
