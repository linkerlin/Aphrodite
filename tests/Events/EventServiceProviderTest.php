<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Events;

use Aphrodite\Container\Container;
use Aphrodite\Events\EventDispatcher;
use Aphrodite\Events\EventServiceProvider;
use PHPUnit\Framework\TestCase;

class EventServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        $provider = new EventServiceProvider();
        $provider->register($this->container);
    }

    public function testEventDispatcherIsBound(): void
    {
        $dispatcher = $this->container->get(EventDispatcher::class);

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }

    public function testEventsAliasIsBound(): void
    {
        $dispatcher = $this->container->get('events');

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }

    public function testEventDispatcherAliasIsBound(): void
    {
        $dispatcher = $this->container->get('event.dispatcher');

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }

    public function testEventDispatcherIsSingleton(): void
    {
        $dispatcher1 = $this->container->get(EventDispatcher::class);
        $dispatcher2 = $this->container->get(EventDispatcher::class);

        $this->assertSame($dispatcher1, $dispatcher2);
    }

    public function testEventDispatcherIsFunctional(): void
    {
        $dispatcher = $this->container->get(EventDispatcher::class);

        $called = false;
        $dispatcher->listen('test.event', function () use (&$called) {
            $called = true;
        });

        $dispatcher->dispatch('test.event');

        $this->assertTrue($called);
    }

    public function testAllAliasesReturnSameInstance(): void
    {
        $instance1 = $this->container->get(EventDispatcher::class);
        $instance2 = $this->container->get('events');
        $instance3 = $this->container->get('event.dispatcher');

        $this->assertSame($instance1, $instance2);
        $this->assertSame($instance2, $instance3);
    }
}
