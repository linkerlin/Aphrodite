<?php

declare(strict_types=1);

namespace Aphrodite\Cache;

/**
 * Array-based cache (in-memory).
 */
class ArrayCache implements CacheInterface
{
    protected array $store = [];
    protected array $expiry = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->expiry[$key]) && $this->expiry[$key] > 0 && $this->expiry[$key] < time()) {
            unset($this->store[$key], $this->expiry[$key]);
            return $default;
        }

        return $this->store[$key] ?? $default;
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $this->store[$key] = $value;
        $this->expiry[$key] = $ttl > 0 ? time() + $ttl : 0;

        return true;
    }

    public function has(string $key): bool
    {
        if (isset($this->expiry[$key]) && $this->expiry[$key] > 0 && $this->expiry[$key] < time()) {
            unset($this->store[$key], $this->expiry[$key]);
            return false;
        }

        return isset($this->store[$key]);
    }

    public function forget(string $key): bool
    {
        unset($this->store[$key], $this->expiry[$key]);
        return true;
    }

    public function flush(): bool
    {
        $this->store = [];
        $this->expiry = [];
        return true;
    }
}
