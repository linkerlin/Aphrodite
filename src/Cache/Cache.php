<?php

declare(strict_types=1);

namespace Aphrodite\Cache;

require_once __DIR__ . '/CacheInterface.php';
require_once __DIR__ . '/FileCache.php';
require_once __DIR__ . '/ArrayCache.php';

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
