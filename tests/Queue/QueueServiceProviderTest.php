<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Queue;

use Aphrodite\Container\Container;
use Aphrodite\Queue\QueueInterface;
use Aphrodite\Queue\QueueServiceProvider;
use PHPUnit\Framework\TestCase;

class QueueServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        $provider = new QueueServiceProvider();
        $provider->register($this->container);
    }

    public function testQueueInterfaceIsBound(): void
    {
        $queue = $this->container->get(QueueInterface::class);

        $this->assertInstanceOf(QueueInterface::class, $queue);
    }

    public function testQueueAliasIsBound(): void
    {
        $queue = $this->container->get('queue');

        $this->assertInstanceOf(QueueInterface::class, $queue);
    }

    public function testQueueDriverAliasIsBound(): void
    {
        $queue = $this->container->get('queue.driver');

        $this->assertInstanceOf(QueueInterface::class, $queue);
    }

    public function testQueueIsSingleton(): void
    {
        $queue1 = $this->container->get(QueueInterface::class);
        $queue2 = $this->container->get(QueueInterface::class);

        $this->assertSame($queue1, $queue2);
    }

    public function testQueueUsesFileQueueWhenPathConfigured(): void
    {
        $container = new Container();
        $container->instance('queue.path', sys_get_temp_dir() . '/custom_queue');

        $provider = new QueueServiceProvider();
        $provider->register($container);

        $queue = $container->get(QueueInterface::class);

        $this->assertInstanceOf(QueueInterface::class, $queue);
    }

    public function testQueueServiceCanBeReplaced(): void
    {
        $customQueue = new class implements QueueInterface {
            public function push(string $job, array $data = [], ?string $queue = null): string
            {
                return 'test_id';
            }
            public function later(int $delay, string $job, array $data = [], ?string $queue = null): string
            {
                return 'test_id';
            }
            public function pop(?string $queue = null): ?\Aphrodite\Queue\QueuedJob
            {
                return null;
            }
            public function acknowledge(\Aphrodite\Queue\QueuedJob $job): bool
            {
                return true;
            }
            public function release(\Aphrodite\Queue\QueuedJob $job, int $delay = 0): bool
            {
                return true;
            }
        };

        $this->container->instance(QueueInterface::class, $customQueue);

        $queue = $this->container->get(QueueInterface::class);

        $this->assertSame($customQueue, $queue);
    }
}
