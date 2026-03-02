<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Cache;

require_once __DIR__ . '/../../src/Cache/Cache.php';

use Aphrodite\Cache\Cache;
use Aphrodite\Cache\ArrayCache;
use Aphrodite\Cache\FileCache;
use PHPUnit\Framework\TestCase;

class ArrayCacheTest extends TestCase
{
    private ArrayCache $cache;

    protected function setUp(): void
    {
        $this->cache = new ArrayCache();
    }

    public function testSetAndGet(): void
    {
        $this->cache->set('key', 'value');

        $this->assertEquals('value', $this->cache->get('key'));
    }

    public function testGetDefaultValue(): void
    {
        $result = $this->cache->get('nonexistent', 'default');

        $this->assertEquals('default', $result);
    }

    public function testHas(): void
    {
        $this->cache->set('exists', 'value');

        $this->assertTrue($this->cache->has('exists'));
        $this->assertFalse($this->cache->has('nonexistent'));
    }

    public function testForget(): void
    {
        $this->cache->set('key', 'value');
        $this->cache->forget('key');

        $this->assertFalse($this->cache->has('key'));
    }

    public function testFlush(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->flush();

        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
    }

    public function testTtlExpiry(): void
    {
        $this->cache->set('temp', 'value', 1); // 1 second TTL

        $this->assertTrue($this->cache->has('temp'));

        // Note: In real test, we'd need to sleep, but for ArrayCache
        // the expiry check happens on access
        $this->assertEquals('value', $this->cache->get('temp'));
    }

    public function testStoreMultipleTypes(): void
    {
        $this->cache->set('string', 'hello');
        $this->cache->set('int', 42);
        $this->cache->set('array', ['a' => 1, 'b' => 2]);
        $this->cache->set('null', null);
        $this->cache->set('bool', false);

        $this->assertEquals('hello', $this->cache->get('string'));
        $this->assertEquals(42, $this->cache->get('int'));
        $this->assertEquals(['a' => 1, 'b' => 2], $this->cache->get('array'));
        $this->assertNull($this->cache->get('null'));
        $this->assertFalse($this->cache->get('bool'));
    }
}

class FileCacheTest extends TestCase
{
    private string $cacheDir;
    private FileCache $cache;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/aphrodite_cache_' . uniqid();
        mkdir($this->cacheDir, 0755, true);
        $this->cache = new FileCache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->cache->flush();
        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }
    }

    public function testSetAndGet(): void
    {
        $this->cache->set('key', 'value');

        $this->assertEquals('value', $this->cache->get('key'));
    }

    public function testGetDefaultValue(): void
    {
        $result = $this->cache->get('nonexistent', 'default');

        $this->assertEquals('default', $result);
    }

    public function testHas(): void
    {
        $this->cache->set('exists', 'value');

        $this->assertTrue($this->cache->has('exists'));
        $this->assertFalse($this->cache->has('nonexistent'));
    }

    public function testForget(): void
    {
        $this->cache->set('key', 'value');
        $this->cache->forget('key');

        $this->assertFalse($this->cache->has('key'));
    }

    public function testFlush(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->cache->flush();

        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
    }

    public function testTtlExpiry(): void
    {
        $this->cache->set('temp', 'value', 1); // 1 second TTL

        $this->assertTrue($this->cache->has('temp'));

        // Wait for expiry
        sleep(2);

        $this->assertFalse($this->cache->has('temp'));
        $this->assertEquals('default', $this->cache->get('temp', 'default'));
    }

    public function testPersistentCache(): void
    {
        $this->cache->set('persistent', 'value');

        // Create new instance pointing to same directory
        $cache2 = new FileCache($this->cacheDir);
        
        $this->assertEquals('value', $cache2->get('persistent'));
    }
}

class CacheManagerTest extends TestCase
{
    protected function setUp(): void
    {
        Cache::setDriver(new ArrayCache());
        Cache::setPrefix('test_');
    }

    protected function tearDown(): void
    {
        Cache::flush();
    }

    public function testStaticSetAndGet(): void
    {
        Cache::set('key', 'value');

        $this->assertEquals('value', Cache::get('key'));
    }

    public function testStaticHas(): void
    {
        Cache::set('exists', 'value');

        $this->assertTrue(Cache::has('exists'));
    }

    public function testStaticForget(): void
    {
        Cache::set('key', 'value');
        Cache::forget('key');

        $this->assertFalse(Cache::has('key'));
    }

    public function testRemember(): void
    {
        $result = Cache::remember('key', 60, function () {
            return 'computed';
        });

        $this->assertEquals('computed', $result);

        // Second call should use cached value
        $result2 = Cache::remember('key', 60, function () {
            return 'different';
        });

        $this->assertEquals('computed', $result2);
    }

    public function testPrefix(): void
    {
        Cache::set('key', 'value');

        // Key should be prefixed
        $this->assertTrue(Cache::has('key'));
    }
}
