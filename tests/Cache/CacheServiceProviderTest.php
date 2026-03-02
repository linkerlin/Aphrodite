<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Cache;

use Aphrodite\Cache\Cache;
use Aphrodite\Cache\CacheInterface;
use Aphrodite\Cache\CacheServiceProvider;
use Aphrodite\Cache\FileCache;
use Aphrodite\Container\Container;
use PHPUnit\Framework\TestCase;

class CacheServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        $provider = new CacheServiceProvider();
        $provider->register($this->container);
    }

    public function testCacheInterfaceIsBound(): void
    {
        $cache = $this->container->get(CacheInterface::class);

        $this->assertInstanceOf(CacheInterface::class, $cache);
    }

    public function testCacheAliasIsBound(): void
    {
        $cache = $this->container->get('cache');

        $this->assertInstanceOf(CacheInterface::class, $cache);
    }

    public function testCacheDriverAliasIsBound(): void
    {
        $cache = $this->container->get('cache.driver');

        $this->assertInstanceOf(CacheInterface::class, $cache);
    }

    public function testDefaultCacheIsFileCache(): void
    {
        $cache = $this->container->get(CacheInterface::class);

        $this->assertInstanceOf(FileCache::class, $cache);
    }

    public function testCacheIsSingleton(): void
    {
        $cache1 = $this->container->get(CacheInterface::class);
        $cache2 = $this->container->get(CacheInterface::class);

        $this->assertSame($cache1, $cache2);
    }

    public function testCacheUsesCustomPath(): void
    {
        $container = new Container();
        $container->instance('cache.path', sys_get_temp_dir() . '/custom_cache');

        $provider = new CacheServiceProvider();
        $provider->register($container);

        $cache = $container->get(CacheInterface::class);

        $this->assertInstanceOf(FileCache::class, $cache);
    }

    public function testCacheServiceCanBeReplaced(): void
    {
        $customCache = new class implements CacheInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }
            public function set(string $key, mixed $value, int $ttl = 0): bool
            {
                return true;
            }
            public function has(string $key): bool
            {
                return false;
            }
            public function forget(string $key): bool
            {
                return true;
            }
            public function flush(): bool
            {
                return true;
            }
        };

        $this->container->instance(CacheInterface::class, $customCache);

        $cache = $this->container->get(CacheInterface::class);

        $this->assertSame($customCache, $cache);
    }
}
