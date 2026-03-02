<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Events;

require_once __DIR__ . '/../../src/Events/EventDispatcher.php';

use Aphrodite\Events\EventDispatcher;
use Aphrodite\Events\Events;
use Aphrodite\Events\Event;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function testListenAndDispatch(): void
    {
        $this->dispatcher->listen('test.event', function ($data) {
            return 'handled: ' . $data;
        });

        $results = $this->dispatcher->dispatch('test.event', 'test_data');

        $this->assertCount(1, $results);
        $this->assertEquals('handled: test_data', $results[0]);
    }

    public function testMultipleListeners(): void
    {
        $this->dispatcher->listen('test.event', function () {
            return 'first';
        });

        $this->dispatcher->listen('test.event', function () {
            return 'second';
        });

        $results = $this->dispatcher->dispatch('test.event');

        $this->assertCount(2, $results);
        $this->assertEquals('first', $results[0]);
        $this->assertEquals('second', $results[1]);
    }

    public function testPriorityOrdering(): void
    {
        $this->dispatcher->listen('test.event', function () {
            return 'low';
        }, 0);

        $this->dispatcher->listen('test.event', function () {
            return 'high';
        }, 100);

        $results = $this->dispatcher->dispatch('test.event');

        // Higher priority should execute first
        $this->assertEquals('high', $results[0]);
        $this->assertEquals('low', $results[1]);
    }

    public function testHasListeners(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners('test.event'));

        $this->dispatcher->listen('test.event', function () {});

        $this->assertTrue($this->dispatcher->hasListeners('test.event'));
    }

    public function testGetListeners(): void
    {
        $this->dispatcher->listen('test.event', function () {});
        $this->dispatcher->listen('test.event', function () {});

        $listeners = $this->dispatcher->getListeners('test.event');

        $this->assertCount(2, $listeners);
    }

    public function testOnceListener(): void
    {
        $callCount = 0;

        $this->dispatcher->once('test.event', function () use (&$callCount) {
            $callCount++;
        });

        $this->dispatcher->dispatch('test.event');
        $this->dispatcher->dispatch('test.event');

        $this->assertEquals(1, $callCount);
    }

    public function testRemoveListener(): void
    {
        $callback = function () {};
        
        $this->dispatcher->listen('test.event', $callback);
        $this->dispatcher->removeListener('test.event', $callback);

        $this->assertFalse($this->dispatcher->hasListeners('test.event'));
    }

    public function testForgetEvent(): void
    {
        $this->dispatcher->listen('test.event', function () {});
        $this->dispatcher->forget('test.event');

        $this->assertFalse($this->dispatcher->hasListeners('test.event'));
    }

    public function testUntilReturnsFirstResult(): void
    {
        $this->dispatcher->listen('test.event', function () {
            return null;
        });

        $this->dispatcher->listen('test.event', function () {
            return 'found';
        });

        $result = $this->dispatcher->until('test.event');

        $this->assertEquals('found', $result);
    }

    public function testUntilReturnsNull(): void
    {
        $this->dispatcher->listen('test.event', function () {
            return null;
        });

        $result = $this->dispatcher->until('test.event');

        $this->assertNull($result);
    }

    public function testFiredFlag(): void
    {
        $this->assertFalse($this->dispatcher->fired('test.event'));

        $this->dispatcher->dispatch('test.event');

        $this->assertTrue($this->dispatcher->fired('test.event'));
    }

    public function testClearFired(): void
    {
        $this->dispatcher->dispatch('test.event');
        $this->dispatcher->clearFired('test.event');

        $this->assertFalse($this->dispatcher->fired('test.event'));
    }

    public function testDispatchWithMultipleArgs(): void
    {
        $this->dispatcher->listen('test.event', function ($a, $b, $c) {
            return $a + $b + $c;
        });

        $results = $this->dispatcher->dispatch('test.event', 1, 2, 3);

        $this->assertEquals(6, $results[0]);
    }
}

class EventTest extends TestCase
{
    public function testStopPropagation(): void
    {
        $event = new Event();

        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }
}

class EventsFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        Events::setDispatcher(new EventDispatcher());
    }

    public function testStaticListen(): void
    {
        Events::listen('test.event', function ($data) {
            return 'handled: ' . $data;
        });

        $dispatcher = Events::getDispatcher();
        $this->assertTrue($dispatcher->hasListeners('test.event'));
    }

    public function testStaticDispatch(): void
    {
        Events::listen('test.event', function ($data) {
            return $data;
        });

        $results = Events::dispatch('test.event', 'test_data');

        $this->assertCount(1, $results);
        $this->assertEquals('test_data', $results[0]);
    }

    public function testStaticUntil(): void
    {
        Events::listen('test.event', function () {
            return null;
        });

        Events::listen('test.event', function () {
            return 'found';
        });

        $result = Events::until('test.event');

        $this->assertEquals('found', $result);
    }
}
