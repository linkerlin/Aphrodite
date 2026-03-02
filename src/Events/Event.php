<?php

declare(strict_types=1);

namespace Aphrodite\Events;

/**
 * Base event class.
 */
class Event
{
    protected bool $propagationStopped = false;

    /**
     * Stop event propagation.
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Check if propagation is stopped.
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
