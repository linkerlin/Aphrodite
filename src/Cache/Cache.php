<?php

declare(strict_types=1);

namespace Aphrodite\Cache;

/**
 * Cache interface.
 */
interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, int $ttl = 0): bool;
    public function has(string $key): bool;
    public function forget(string $key): bool;
    public function flush(): bool;
}

/**
 * File-based cache implementation.
 */
class FileCache implements CacheInterface
{
    protected string $path;
    protected array $locks = [];

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? dirname(__DIR__, 2) . '/storage/cache';

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data['expires'] > 0 && $data['expires'] < time()) {
            @unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $file = $this->getFilePath($key);

        $expires = $ttl > 0 ? time() + $ttl : 0;

        $data = [
            'value' => $value,
            'expires' => $expires,
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return false;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data['expires'] > 0 && $data['expires'] < time()) {
            @unlink($file);
            return false;
        }

        return true;
    }

    public function forget(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    public function flush(): bool
    {
        $files = glob($this->path . '/*.cache');

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    protected function getFilePath(string $key): string
    {
        $hash = md5($key);
        return $this->path . '/' . $hash . '.cache';
    }
}

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

/**
 * Cache manager with tagging support.
 */
class Cache
{
    protected static ?CacheInterface $driver = null;
    protected static ?string $prefix = 'aphrodite_';

    /**
     * Get cache driver instance.
     */
    public static function getDriver(): CacheInterface
    {
        if (self::$driver === null) {
            self::$driver = new ArrayCache();
        }

        return self::$driver;
    }

    /**
     * Set cache driver.
     */
    public static function setDriver(CacheInterface $driver): void
    {
        self::$driver = $driver;
    }

    /**
     * Set key prefix.
     */
    public static function setPrefix(string $prefix): void
    {
        self::$prefix = $prefix;
    }

    /**
     * Get value from cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getDriver()->get(self::$prefix . $key, $default);
    }

    /**
     * Store value in cache.
     */
    public static function set(string $key, mixed $value, int $ttl = 0): bool
    {
        return self::getDriver()->set(self::$prefix . $key, $value, $ttl);
    }

    /**
     * Check if key exists.
     */
    public static function has(string $key): bool
    {
        return self::getDriver()->has(self::$prefix . $key);
    }

    /**
     * Remove value from cache.
     */
    public static function forget(string $key): bool
    {
        return self::getDriver()->forget(self::$prefix . $key);
    }

    /**
     * Clear all cache.
     */
    public static function flush(): bool
    {
        return self::getDriver()->flush();
    }

    /**
     * Remember value if not exists.
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        if (self::has($key)) {
            return self::get($key);
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }
}
