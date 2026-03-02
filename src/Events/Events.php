<?php

declare(strict_types=1);

namespace Aphrodite\Events;

require_once __DIR__ . '/EventSubscriberInterface.php';
require_once __DIR__ . '/Event.php';
require_once __DIR__ . '/EventDispatcher.php';

/**
 * Global event dispatcher instance.
 */
class Events
{
    protected static ?EventDispatcher $dispatcher = null;

    /**
     * Get dispatcher instance.
     */
    public static function getDispatcher(): EventDispatcher
    {
        if (self::$dispatcher === null) {
            self::$dispatcher = new EventDispatcher();
        }
        return self::$dispatcher;
    }

    /**
     * Set dispatcher instance.
     */
    public static function setDispatcher(EventDispatcher $dispatcher): void
    {
        self::$dispatcher = $dispatcher;
    }

    /**
     * Register an event listener.
     */
    public static function listen(string $event, callable $callback, int $priority = 0): self
    {
        self::getDispatcher()->listen($event, $callback, $priority);
        return new self();
    }

    /**
     * Fire an event.
     */
    public static function dispatch(string $event, mixed ...$args): array
    {
        return self::getDispatcher()->dispatch($event, ...$args);
    }

    /**
     * Fire until first result.
     */
    public static function until(string $event, mixed ...$args): mixed
    {
        return self::getDispatcher()->until($event, ...$args);
    }
}
