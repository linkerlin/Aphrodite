<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Events;

use Aphrodite\Events\Event;
use Aphrodite\Events\ListenerInterface;
use Aphrodite\Events\TypedEvent;
use Aphrodite\Events\TypedEventDispatcher;
use PHPUnit\Framework\TestCase;

class TypedEventTest extends TestCase
{
    // Test event classes

    private function createTestEventClass(): string
    {
        return new class extends TypedEvent {
            public static function getName(): string
            {
                return 'test.event';
            }
        }::class;
    }

    public function testTypedEventDispatcherCanBeCreated(): void
    {
        $dispatcher = new TypedEventDispatcher();

        $this->assertInstanceOf(TypedEventDispatcher::class, $dispatcher);
    }

    public function testListenRegistersHandlerForEventType(): void
    {
        $dispatcher = new TypedEventDispatcher();
        $event = new class extends TypedEvent {};

        $called = false;
        $dispatcher->listen($event::class, function (object $e) use (&$called) {
            $called = true;
        });

        $dispatcher->dispatch($event);

        $this->assertTrue($called);
    }

    public function testDispatchReturnsResults(): void
    {
        $dispatcher = new TypedEventDispatcher();
        $event = new class extends TypedEvent {};

        $dispatcher->listen($event::class, fn() => 'result1');
        $dispatcher->listen($event::class, fn() => 'result2');

        $results = $dispatcher->dispatch($event);

        $this->assertEquals(['result1', 'result2'], $results);
    }

    public function testHasListenersReturnsTrueWhenListenersExist(): void
    {
        $dispatcher = new TypedEventDispatcher();
        $event = new class extends TypedEvent {};

        $this->assertFalse($dispatcher->hasListeners($event::class));

        $dispatcher->listen($event::class, fn() => null);

        $this->assertTrue($dispatcher->hasListeners($event::class));
    }

    public function testGetListenersForEventReturnsHandlers(): void
    {
        $dispatcher = new TypedEventDispatcher();
        $event = new class extends TypedEvent {};

        $handler = fn() => 'test';
        $dispatcher->listen($event::class, $handler);

        $listeners = $dispatcher->getListenersForEvent($event::class);

        $this->assertCount(1, $listeners);
    }

    public function testForgetRemovesListeners(): void
    {
        $dispatcher = new TypedEventDispatcher();
        $event = new class extends TypedEvent {};

        $dispatcher->listen($event::class, fn() => null);
        $dispatcher->forget($event::class);

        $this->assertFalse($dispatcher->hasListeners($event::class));
    }

    public function testRegisterListenerRegistersListenerClass(): void
    {
        $dispatcher = new TypedEventDispatcher();

        $listenerClass = new class implements ListenerInterface {
            public static bool $wasCalled = false;

            public function handle(object $event): void
            {
                self::$wasCalled = true;
            }

            public function getEventType(): string
            {
                return TestEventForTyped::class;
            }
        }::class;

        $dispatcher->registerListener($listenerClass);
        $dispatcher->dispatch(new TestEventForTyped());

        $this->assertTrue($listenerClass::$wasCalled);
    }

    public function testSubscribeRegistersMultipleListeners(): void
    {
        $dispatcher = new TypedEventDispatcher();

        $listener1Class = new class implements ListenerInterface {
            public static bool $wasCalled = false;

            public function handle(object $event): void
            {
                self::$wasCalled = true;
            }

            public function getEventType(): string
            {
                return TestEventForTyped::class;
            }
        }::class;

        $dispatcher->subscribe([$listener1Class]);
        $dispatcher->dispatch(new TestEventForTyped());

        $this->assertTrue($listener1Class::$wasCalled);
    }

    public function testEventPropagationCanBeStopped(): void
    {
        $dispatcher = new TypedEventDispatcher();

        $event = new class extends TypedEvent {};

        $firstCalled = false;
        $secondCalled = false;

        $dispatcher->listen($event::class, function (object $e) use (&$firstCalled) {
            $firstCalled = true;
            $e->stopPropagation();
        });

        $dispatcher->listen($event::class, function () use (&$secondCalled) {
            $secondCalled = true;
        });

        $dispatcher->dispatch($event);

        $this->assertTrue($firstCalled);
        $this->assertFalse($secondCalled);
    }

    public function testTypedEventStoresPayload(): void
    {
        $payload = ['key' => 'value', 'number' => 42];
        $event = new class($payload) extends TypedEvent {};

        $this->assertEquals($payload, $event->getPayload());
    }

    public function testTypedEventStoresTimestamp(): void
    {
        $before = microtime(true);
        $event = new class extends TypedEvent {};
        $after = microtime(true);

        $this->assertGreaterThanOrEqual($before, $event->getTimestamp());
        $this->assertLessThanOrEqual($after, $event->getTimestamp());
    }

    public function testTypedEventGetNameReturnsClassName(): void
    {
        $event = new TestEventForTyped();

        $this->assertEquals(TestEventForTyped::class, $event::getName());
    }

    public function testEventExtendsBaseEvent(): void
    {
        $event = new TestEventForTyped();

        $this->assertInstanceOf(Event::class, $event);
    }
}

// Test helper class
class TestEventForTyped extends TypedEvent
{
}
