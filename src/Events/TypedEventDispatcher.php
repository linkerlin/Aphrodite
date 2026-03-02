<?php

declare(strict_types=1);

namespace Aphrodite\Events;

use Closure;

/**
 * Strongly-typed event dispatcher.
 */
class TypedEventDispatcher
{
    /**
     * @var array<string, array<int, callable>>
     */
    private array $listeners = [];

    /**
     * @var array<class-string, class-string<ListenerInterface>>
     */
    private array $listenerMap = [];

    /**
     * Register a typed listener.
     */
    public function listen(string $eventType, callable $handler): self
    {
        $this->listeners[$eventType][] = $handler;
        return $this;
    }

    /**
     * Register a listener class.
     *
     * @param class-string<ListenerInterface> $listenerClass
     */
    public function registerListener(string $listenerClass): self
    {
        $listener = new $listenerClass();
        $eventType = $listener->getEventType();

        $this->listeners[$eventType][] = fn(object $event) => $listener->handle($event);
        $this->listenerMap[$eventType] = $listenerClass;

        return $this;
    }

    /**
     * Dispatch a typed event.
     */
    public function dispatch(object $event): array
    {
        $eventType = $event::class;
        $results = [];

        if (isset($this->listeners[$eventType])) {
            foreach ($this->listeners[$eventType] as $handler) {
                // Check if propagation is stopped before each handler
                if ($event instanceof Event && $event->isPropagationStopped()) {
                    return $results;
                }

                $results[] = $handler($event);
            }
        }

        return $results;
    }

    /**
     * Check if event type has listeners.
     */
    public function hasListeners(string $eventType): bool
    {
        return !empty($this->listeners[$eventType]);
    }

    /**
     * Get all listeners for an event type.
     *
     * @return array<int, callable>
     */
    public function getListenersForEvent(string $eventType): array
    {
        return $this->listeners[$eventType] ?? [];
    }

    /**
     * Remove all listeners for an event type.
     */
    public function forget(string $eventType): self
    {
        unset($this->listeners[$eventType], $this->listenerMap[$eventType]);
        return $this;
    }

    /**
     * Subscribe multiple listeners.
     *
     * @param array<class-string<ListenerInterface>> $listeners
     */
    public function subscribe(array $listeners): self
    {
        foreach ($listeners as $listener) {
            $this->registerListener($listener);
        }
        return $this;
    }
}
