<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Foundation;

use Aphrodite\Container\Container;
use Aphrodite\Container\ContainerInterface;
use Aphrodite\Container\ServiceProviderInterface;
use Aphrodite\Foundation\Application;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        $this->tempPath = sys_get_temp_dir() . '/aphrodite_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        // Reset static instance
        Container::setInstance(null);
    }

    // === Construction Tests ===

    public function testConstructorSetsBasePath(): void
    {
        $app = new Application($this->tempPath);

        $this->assertEquals($this->tempPath, $app->basePath());
    }

    public function testConstructorSetsDefaultEnvironment(): void
    {
        $app = new Application($this->tempPath);

        $this->assertEquals('production', $app->environment());
    }

    public function testConstructorSetsCustomEnvironment(): void
    {
        $app = new Application($this->tempPath, 'development');

        $this->assertEquals('development', $app->environment());
    }

    // === Base Bindings Tests ===

    public function testAppIsRegistered(): void
    {
        $app = new Application($this->tempPath);

        $this->assertSame($app, $app->get('app'));
    }

    public function testContainerInterfaceIsRegistered(): void
    {
        $app = new Application($this->tempPath);

        $this->assertSame($app, $app->get(ContainerInterface::class));
    }

    public function testContainerIsRegistered(): void
    {
        $app = new Application($this->tempPath);

        $this->assertSame($app, $app->get(Container::class));
    }

    // === Path Tests ===

    public function testBasePathWithSubPath(): void
    {
        $app = new Application($this->tempPath);

        $this->assertEquals($this->tempPath . DIRECTORY_SEPARATOR . 'sub', $app->basePath('sub'));
    }

    public function testConfigPath(): void
    {
        $app = new Application($this->tempPath);

        $this->assertEquals($this->tempPath . DIRECTORY_SEPARATOR . 'config', $app->configPath());
    }

    public function testConfigPathWithSubPath(): void
    {
        $app = new Application($this->tempPath);

        $expected = $this->tempPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
        $this->assertEquals($expected, $app->configPath('app.php'));
    }

    public function testStoragePath(): void
    {
        $app = new Application($this->tempPath);

        $this->assertEquals($this->tempPath . DIRECTORY_SEPARATOR . 'storage', $app->storagePath());
    }

    public function testStoragePathWithSubPath(): void
    {
        $app = new Application($this->tempPath);

        $expected = $this->tempPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        $this->assertEquals($expected, $app->storagePath('logs'));
    }

    // === Environment Tests ===

    public function testIsLocal(): void
    {
        $app = new Application($this->tempPath, 'local');
        $this->assertTrue($app->isLocal());

        $appProd = new Application($this->tempPath, 'production');
        $this->assertFalse($appProd->isLocal());
    }

    public function testIsProduction(): void
    {
        $app = new Application($this->tempPath, 'production');
        $this->assertTrue($app->isProduction());

        $appDev = new Application($this->tempPath, 'development');
        $this->assertFalse($appDev->isProduction());
    }

    public function testIsTesting(): void
    {
        $app = new Application($this->tempPath, 'testing');
        $this->assertTrue($app->isTesting());

        $appProd = new Application($this->tempPath, 'production');
        $this->assertFalse($appProd->isTesting());
    }

    // === Service Provider Tests ===

    public function testRegisterProvider(): void
    {
        $app = new Application($this->tempPath);

        $provider = new class implements ServiceProviderInterface {
            public bool $registered = false;
            public bool $booted = false;

            public function register(Container $container): void
            {
                $this->registered = true;
                $container->singleton('test_service', fn() => 'test_value');
            }

            public function boot(Container $container): void
            {
                $this->booted = true;
            }
        };

        $app->register($provider);

        $this->assertTrue($provider->registered);
        $this->assertEquals('test_value', $app->get('test_service'));
    }

    public function testBoot(): void
    {
        $app = new Application($this->tempPath);

        $provider = new class implements ServiceProviderInterface {
            public bool $booted = false;

            public function register(Container $container): void
            {
            }

            public function boot(Container $container): void
            {
                $this->booted = true;
            }
        };

        $app->register($provider);
        $this->assertFalse($provider->booted);

        $app->boot();
        $this->assertTrue($provider->booted);
    }

    public function testBootOnlyOnce(): void
    {
        $app = new Application($this->tempPath);

        $bootCount = 0;
        $provider = new class($bootCount) implements ServiceProviderInterface {
            public function register(Container $container): void
            {
            }

            public function boot(Container $container): void
            {
            }
        };

        $app->register($provider);
        $app->boot();
        $app->boot();

        $this->assertTrue($app->isBooted());
    }

    public function testProviderBootedImmediatelyIfAppAlreadyBooted(): void
    {
        $app = new Application($this->tempPath);

        $provider = new class implements ServiceProviderInterface {
            public bool $booted = false;

            public function register(Container $container): void
            {
            }

            public function boot(Container $container): void
            {
                $this->booted = true;
            }
        };

        $app->boot();
        $app->register($provider);

        $this->assertTrue($provider->booted);
    }

    public function testGetProviders(): void
    {
        $app = new Application($this->tempPath);

        $provider1 = new class implements ServiceProviderInterface {
            public function register(Container $container): void {}
            public function boot(Container $container): void {}
        };

        $provider2 = new class implements ServiceProviderInterface {
            public function register(Container $container): void {}
            public function boot(Container $container): void {}
        };

        $app->register($provider1);
        $app->register($provider2);

        $providers = $app->getProviders();

        $this->assertCount(2, $providers);
    }

    // === Factory Method Tests ===

    public function testCreateFromEnvironment(): void
    {
        putenv('APP_ENV=testing');

        $app = Application::create($this->tempPath);

        $this->assertEquals('testing', $app->environment());

        putenv('APP_ENV'); // Clear
    }

    public function testCreateDefaultsToProduction(): void
    {
        // Ensure APP_ENV is not set
        putenv('APP_ENV');

        $app = Application::create($this->tempPath);

        $this->assertEquals('production', $app->environment());
    }
}
