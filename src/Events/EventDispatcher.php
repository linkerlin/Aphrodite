<?php

declare(strict_types=1);

namespace Aphrodite\Events;

/**
 * Event dispatcher for publishing and listening to events.
 */
class EventDispatcher
{
    /** @var array<string, array<int, array{callback: callable, priority: int}>> */
    protected array $listeners = [];

    /** @var array<string, bool> */
    protected array $fired = [];

    /**
     * Register an event listener.
     */
    public function listen(string $event, callable $callback, int $priority = 0): self
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        usort($this->listeners[$event], fn($a, $b) => $b['priority'] <=> $a['priority']);

        return $this;
    }

    /**
     * Register a one-time event listener.
     */
    public function once(string $event, callable $callback): self
    {
        $wrapper = function (...$args) use ($callback, $event, &$wrapper) {
            $result = $callback(...$args);
            $this->removeListener($event, $wrapper);
            return $result;
        };

        return $this->listen($event, $wrapper);
    }

    /**
     * Remove an event listener.
     */
    public function removeListener(string $event, callable $callback): self
    {
        if (isset($this->listeners[$event])) {
            $this->listeners[$event] = array_filter(
                $this->listeners[$event],
                fn($listener) => $listener['callback'] !== $callback
            );
        }

        return $this;
    }

    /**
     * Remove all listeners for an event.
     */
    public function forget(string $event): self
    {
        unset($this->listeners[$event]);
        return $this;
    }

    /**
     * Fire an event and call all listeners.
     */
    public function dispatch(string $event, mixed ...$args): array
    {
        $results = [];
        $this->fired[$event] = true;

        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $results[] = ($listener['callback'])(...$args);
            }
        }

        return $results;
    }

    /**
     * Fire event and return first non-null result.
     */
    public function until(string $event, mixed ...$args): mixed
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $result = ($listener['callback'])(...$args);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Check if event has listeners.
     */
    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]);
    }

    /**
     * Get all listeners for an event.
     */
    public function getListeners(string $event): array
    {
        return $this->listeners[$event] ?? [];
    }

    /**
     * Check if event was fired.
     */
    public function fired(string $event): bool
    {
        return $this->fired[$event] ?? false;
    }

    /**
     * Clear fired status.
     */
    public function clearFired(string $event): self
    {
        unset($this->fired[$event]);
        return $this;
    }

    /**
     * Register a subscriber.
     */
    public function subscribe(object $subscriber): self
    {
        if (method_exists($subscriber, 'subscribe')) {
            $subscriber->subscribe($this);
        }
        return $this;
    }
}
