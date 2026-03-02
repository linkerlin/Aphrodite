<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Container;

use Aphrodite\Container\Container;
use Aphrodite\Container\ContainerException;
use Aphrodite\Container\NotFoundException;
use Aphrodite\Container\ServiceProviderInterface;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    // === Basic Binding Tests ===

    public function testBindAndHas(): void
    {
        $this->container->bind('foo', fn() => 'bar');

        $this->assertTrue($this->container->has('foo'));
        $this->assertFalse($this->container->has('baz'));
    }

    public function testGetReturnsBoundValue(): void
    {
        $this->container->bind('foo', fn() => 'bar');

        $result = $this->container->get('foo');

        $this->assertEquals('bar', $result);
    }

    public function testGetReturnsNewInstanceEachTime(): void
    {
        $this->container->bind('counter', function () {
            static $count = 0;
            return ++$count;
        });

        $this->assertEquals(1, $this->container->get('counter'));
        $this->assertEquals(2, $this->container->get('counter'));
        $this->assertEquals(3, $this->container->get('counter'));
    }

    // === Singleton Tests ===

    public function testSingletonReturnsSameInstance(): void
    {
        $this->container->singleton('service', fn() => new \stdClass());

        $instance1 = $this->container->get('service');
        $instance2 = $this->container->get('service');

        $this->assertSame($instance1, $instance2);
    }

    public function testInstanceReturnsSameObject(): void
    {
        $object = new \stdClass();
        $object->value = 'test';

        $this->container->instance('my_object', $object);

        $result = $this->container->get('my_object');

        $this->assertSame($object, $result);
    }

    // === Auto-Resolution Tests ===

    public function testAutoResolveSimpleClass(): void
    {
        $instance = $this->container->get(SimpleClass::class);

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testAutoResolveClassWithDependencies(): void
    {
        $instance = $this->container->get(ClassWithDependency::class);

        $this->assertInstanceOf(ClassWithDependency::class, $instance);
        $this->assertInstanceOf(SimpleClass::class, $instance->dependency);
    }

    public function testAutoResolveClassWithDefaultParameter(): void
    {
        $instance = $this->container->get(ClassWithDefault::class);

        $this->assertEquals('default', $instance->value);
    }

    public function testAutoResolveClassWithContainerDependency(): void
    {
        $instance = $this->container->get(ClassWithContainer::class);

        $this->assertInstanceOf(ClassWithContainer::class, $instance);
        $this->assertSame($this->container, $instance->container);
    }

    // === Exception Tests ===

    public function testGetThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('non_existent_service');
    }

    public function testGetThrowsExceptionForNonInstantiableClass(): void
    {
        $this->expectException(ContainerException::class);

        $this->container->get(AbstractClass::class);
    }

    public function testCircularDependencyDetection(): void
    {
        $this->container->bind(CircularA::class, fn($c) => $c->build(CircularA::class));
        $this->container->bind(CircularB::class, fn($c) => $c->build(CircularB::class));

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $this->container->get(CircularA::class);
    }

    public function testCannotResolvePrimitiveWithoutDefault(): void
    {
        $this->expectException(ContainerException::class);

        $this->container->get(ClassWithPrimitive::class);
    }

    // === Alias Tests ===

    public function testAlias(): void
    {
        $this->container->singleton('original', fn() => new \stdClass());
        $this->container->alias('original', 'alias');

        $original = $this->container->get('original');
        $aliased = $this->container->get('alias');

        $this->assertSame($original, $aliased);
    }

    // === Extender Tests ===

    public function testExtend(): void
    {
        $this->container->bind('service', fn() => new \stdClass());
        $this->container->extend('service', function ($service) {
            $service->extended = true;
            return $service;
        });

        $result = $this->container->get('service');

        $this->assertTrue($result->extended);
    }

    public function testExtendSingleton(): void
    {
        $this->container->singleton('service', fn() => new \stdClass());
        $this->container->extend('service', function ($service) {
            $service->extended = true;
            return $service;
        });

        $instance1 = $this->container->get('service');
        $instance2 = $this->container->get('service');

        $this->assertTrue($instance1->extended);
        $this->assertSame($instance1, $instance2);
    }

    // === Service Provider Tests ===

    public function testServiceProvider(): void
    {
        $provider = new class implements ServiceProviderInterface {
            public function register(Container $container): void
            {
                $container->singleton('provided', fn() => 'provided_value');
            }

            public function boot(Container $container): void
            {
                // Boot logic
            }
        };

        $this->container->register($provider);

        $this->assertEquals('provided_value', $this->container->get('provided'));
    }

    // === Call Tests ===

    public function testCallClosure(): void
    {
        $this->container->bind('value', fn() => 42);

        $result = $this->container->call(function ($value) {
            return $value * 2;
        }, ['value' => 21]);

        $this->assertEquals(42, $result);
    }

    public function testCallWithAutoResolution(): void
    {
        $result = $this->container->call(function (SimpleClass $dep) {
            return $dep;
        });

        $this->assertInstanceOf(SimpleClass::class, $result);
    }

    // === Flush Tests ===

    public function testFlush(): void
    {
        $this->container->bind('foo', fn() => 'bar');
        $this->container->singleton('baz', fn() => 'qux');

        $this->container->flush();

        $this->assertFalse($this->container->has('foo'));
        $this->assertFalse($this->container->has('baz'));
    }

    // === Resolved Check ===

    public function testResolved(): void
    {
        $this->container->singleton('service', fn() => new \stdClass());

        $this->assertFalse($this->container->resolved('service'));

        $this->container->get('service');

        $this->assertTrue($this->container->resolved('service'));
    }
}

// === Test Fixtures ===

class SimpleClass
{
}

class ClassWithDependency
{
    public SimpleClass $dependency;

    public function __construct(SimpleClass $dependency)
    {
        $this->dependency = $dependency;
    }
}

class ClassWithDefault
{
    public string $value;

    public function __construct(string $value = 'default')
    {
        $this->value = $value;
    }
}

class ClassWithContainer
{
    public Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }
}

class ClassWithPrimitive
{
    public function __construct(string $requiredValue)
    {
    }
}

abstract class AbstractClass
{
}

class CircularA
{
    public function __construct(CircularB $b)
    {
    }
}

class CircularB
{
    public function __construct(CircularA $a)
    {
    }
}
