<?php

declare(strict_types=1);

namespace Aphrodite\Events;

/**
 * Base class for typed events with payload support.
 *
 * @template T
 */
abstract class TypedEvent extends Event
{
    protected mixed $payload;
    protected float $timestamp;

    public function __construct(mixed $payload = null)
    {
        $this->payload = $payload;
        $this->timestamp = microtime(true);
    }

    /**
     * Get the event payload.
     *
     * @return T
     */
    public function getPayload(): mixed
    {
        return $this->payload;
    }

    /**
     * Get the event timestamp.
     */
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    /**
     * Get the event name.
     */
    public static function getName(): string
    {
        return static::class;
    }
}
