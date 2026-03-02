<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Config;

use Aphrodite\Config\Config;
use Aphrodite\Config\Environment;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        Config::clear();
    }

    public function testLoad(): void
    {
        Config::load([
            'app' => [
                'name' => 'TestApp',
                'debug' => true
            ]
        ]);

        $this->assertEquals('TestApp', Config::get('app.name'));
        $this->assertTrue(Config::get('app.debug'));
    }

    public function testLoadMultiple(): void
    {
        Config::load(['a' => 1]);
        Config::load(['b' => 2]);

        $this->assertEquals(1, Config::get('a'));
        $this->assertEquals(2, Config::get('b'));
    }

    public function testSet(): void
    {
        Config::set('database.host', 'localhost');
        Config::set('database.port', 3306);

        $this->assertEquals('localhost', Config::get('database.host'));
        $this->assertEquals(3306, Config::get('database.port'));
    }

    public function testGetWithDefault(): void
    {
        $result = Config::get('nonexistent', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    public function testHas(): void
    {
        Config::set('app.name', 'Test');

        $this->assertTrue(Config::has('app.name'));
        $this->assertFalse(Config::has('nonexistent'));
    }

    public function testAll(): void
    {
        Config::load([
            'app' => ['name' => 'Test'],
            'db' => ['host' => 'localhost']
        ]);

        $all = Config::all();

        $this->assertArrayHasKey('app', $all);
        $this->assertArrayHasKey('db', $all);
    }

    public function testClear(): void
    {
        Config::set('test', 'value');
        Config::clear();

        $this->assertFalse(Config::has('test'));
    }

    public function testNestedKeys(): void
    {
        Config::load([
            'level1' => [
                'level2' => [
                    'level3' => 'deep_value'
                ]
            ]
        ]);

        $this->assertEquals('deep_value', Config::get('level1.level2.level3'));
    }

    public function testOverrideExisting(): void
    {
        Config::set('key', 'old_value');
        Config::set('key', 'new_value');

        $this->assertEquals('new_value', Config::get('key'));
    }
}

class EnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear any existing env vars
        putenv('TEST_VAR');
        unset($_ENV['TEST_VAR']);
    }

    public function testGetDefaultValue(): void
    {
        $result = Environment::get('NONEXISTENT_VAR', 'default');

        $this->assertEquals('default', $result);
    }

    public function testSetAndGet(): void
    {
        Environment::set('TEST_VAR', 'test_value');

        $this->assertEquals('test_value', Environment::get('TEST_VAR'));
    }

    public function testHas(): void
    {
        Environment::set('EXISTS', 'value');

        $this->assertTrue(Environment::has('EXISTS'));
        $this->assertFalse(Environment::has('NONEXISTS'));
    }

    public function testParseBooleanTrue(): void
    {
        Environment::set('BOOL_VAR', 'true');

        $result = Environment::get('BOOL_VAR');

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    public function testParseBooleanFalse(): void
    {
        Environment::set('BOOL_VAR', 'false');

        $result = Environment::get('BOOL_VAR');

        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    public function testParseNull(): void
    {
        Environment::set('NULL_VAR', 'null');

        $result = Environment::get('NULL_VAR');

        $this->assertNull($result);
    }

    public function testParseQuotedString(): void
    {
        Environment::set('QUOTED_VAR', '"quoted value"');

        $result = Environment::get('QUOTED_VAR');

        $this->assertEquals('quoted value', $result);
    }

    public function testGetEnv(): void
    {
        Environment::set('APP_ENV', 'production');

        $this->assertEquals('production', Environment::getEnv());
    }

    public function testIsEnvironment(): void
    {
        Environment::set('APP_ENV', 'development');

        $this->assertTrue(Environment::is('development'));
        $this->assertFalse(Environment::is('production'));
    }

    public function testIsDevelopment(): void
    {
        Environment::set('APP_ENV', 'development');

        $this->assertTrue(Environment::isDevelopment());
    }

    public function testIsProduction(): void
    {
        Environment::set('APP_ENV', 'production');

        $this->assertTrue(Environment::isProduction());
    }
}
