<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Logger;

use Aphrodite\Container\Container;
use Aphrodite\Foundation\Application;
use Aphrodite\Logger\FileLogger;
use Aphrodite\Logger\Level;
use Aphrodite\Logger\LoggerInterface;
use Aphrodite\Logger\LoggerServiceProvider;
use PHPUnit\Framework\TestCase;

class LoggerServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        $provider = new LoggerServiceProvider();
        $provider->register($this->container);
    }

    public function testLoggerInterfaceIsBound(): void
    {
        $logger = $this->container->get(LoggerInterface::class);

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testLoggerAliasIsBound(): void
    {
        $logger = $this->container->get('logger');

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testLogAliasIsBound(): void
    {
        $logger = $this->container->get('log');

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testDefaultLoggerIsFileLogger(): void
    {
        $logger = $this->container->get(LoggerInterface::class);

        $this->assertInstanceOf(FileLogger::class, $logger);
    }

    public function testLoggerIsSingleton(): void
    {
        $logger1 = $this->container->get(LoggerInterface::class);
        $logger2 = $this->container->get(LoggerInterface::class);

        $this->assertSame($logger1, $logger2);
    }

    public function testLoggerUsesCustomPath(): void
    {
        $container = new Container();
        $container->instance('log.path', sys_get_temp_dir() . '/custom.log');

        $provider = new LoggerServiceProvider();
        $provider->register($container);

        $logger = $container->get(LoggerInterface::class);

        $this->assertInstanceOf(FileLogger::class, $logger);
    }

    public function testLoggerUsesApplicationStoragePath(): void
    {
        $container = new Container();
        $app = new Application(sys_get_temp_dir() . '/app_test');
        $container->instance('app', $app);

        $provider = new LoggerServiceProvider();
        $provider->register($container);

        $logger = $container->get(LoggerInterface::class);

        $this->assertInstanceOf(FileLogger::class, $logger);
    }

    public function testLoggerUsesCustomLevel(): void
    {
        $container = new Container();
        $container->instance('log.level', Level::ERROR);

        $provider = new LoggerServiceProvider();
        $provider->register($container);

        $logger = $container->get(LoggerInterface::class);

        $this->assertInstanceOf(FileLogger::class, $logger);
    }
}
