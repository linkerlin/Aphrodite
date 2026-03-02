<?php

declare(strict_types=1);

namespace Aphrodite\Events;

/**
 * Interface for typed event listeners.
 *
 * @template T of object
 */
interface ListenerInterface
{
    /**
     * Handle the event.
     *
     * @param T $event
     */
    public function handle(object $event): void;

    /**
     * Get the event type this listener handles.
     *
     * @return class-string<T>
     */
    public function getEventType(): string;
}
