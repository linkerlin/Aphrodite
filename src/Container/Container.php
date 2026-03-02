<?php

declare(strict_types=1);

namespace Aphrodite\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Lightweight PSR-11 compatible dependency injection container.
 */
class Container implements ContainerInterface
{
    /**
     * @var array<string, array{factory: callable, shared: bool, instance?: mixed}>
     */
    private array $bindings = [];

    /**
     * @var array<string, bool>
     */
    private array $resolved = [];

    /**
     * @var array<string, Closure>
     */
    private array $extenders = [];

    /**
     * @var array<class-string, true>
     */
    private array $buildStack = [];

    /**
     * @var static|null
     */
    private static ?self $instance = null;

    /**
     * Register a binding with the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);

        if ($concrete === null) {
            $concrete = $abstract;
        }

        if (is_string($concrete)) {
            $concrete = fn(Container $c) => $c->build($concrete);
        }

        $this->bindings[$abstract] = [
            'factory' => $concrete,
            'shared' => $shared,
        ];
    }

    /**
     * Register a shared binding (singleton).
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared.
     *
     * @param string $abstract
     * @param mixed $instance
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->bindings[$abstract] = [
            'factory' => fn() => $instance,
            'shared' => true,
            'instance' => $instance,
        ];
        $this->resolved[$abstract] = true;
    }

    /**
     * Alias a type to a different name.
     *
     * @param string $abstract
     * @param string $alias
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->bindings[$alias] = [
            'factory' => fn(Container $c) => $c->get($abstract),
            'shared' => false,
        ];
    }

    /**
     * Extend an abstract type in the container.
     *
     * @param string $abstract
     * @param Closure $closure
     */
    public function extend(string $abstract, Closure $closure): void
    {
        if (!isset($this->extenders[$abstract])) {
            $this->extenders[$abstract] = [];
        }
        $this->extenders[$abstract][] = $closure;
    }

    /**
     * Get an instance from the container.
     *
     * @param string $id
     * @return mixed
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            // Try to auto-resolve
            if (class_exists($id)) {
                return $this->build($id);
            }
            throw new NotFoundException("Service not found: {$id}");
        }

        return $this->resolve($id);
    }

    /**
     * Check if the container has a binding.
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    /**
     * Check if a binding has been resolved.
     */
    public function resolved(string $abstract): bool
    {
        return isset($this->resolved[$abstract]);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @return mixed
     * @throws ContainerException
     */
    private function resolve(string $abstract): mixed
    {
        if (!isset($this->bindings[$abstract])) {
            throw new NotFoundException("Service not found: {$abstract}");
        }

        $binding = $this->bindings[$abstract];

        // Return cached instance for shared bindings
        if ($binding['shared'] && isset($binding['instance'])) {
            return $binding['instance'];
        }

        $instance = ($binding['factory'])($this);

        // Apply extenders
        if (isset($this->extenders[$abstract])) {
            foreach ($this->extenders[$abstract] as $extender) {
                $instance = $extender($instance, $this);
            }
        }

        // Cache shared instances
        if ($binding['shared']) {
            $this->bindings[$abstract]['instance'] = $instance;
        }

        $this->resolved[$abstract] = true;

        return $instance;
    }

    /**
     * Build an instance of a class with auto-wiring.
     *
     * @template T
     * @param class-string<T> $concrete
     * @return T
     * @throws ContainerException
     */
    public function build(string $concrete): object
    {
        // Circular dependency detection
        if (isset($this->buildStack[$concrete])) {
            throw new ContainerException(
                "Circular dependency detected: {$concrete} -> " . implode(' -> ', array_keys($this->buildStack))
            );
        }

        $this->buildStack[$concrete] = true;

        try {
            $reflection = new ReflectionClass($concrete);

            if (!$reflection->isInstantiable()) {
                throw new ContainerException("Class {$concrete} is not instantiable");
            }

            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                return new $concrete();
            }

            $dependencies = $this->resolveDependencies($constructor->getParameters());

            return $reflection->newInstanceArgs($dependencies);
        } catch (ReflectionException $e) {
            throw new ContainerException("Failed to build {$concrete}: " . $e->getMessage(), 0, $e);
        } finally {
            unset($this->buildStack[$concrete]);
        }
    }

    /**
     * Resolve a list of dependencies.
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     * @throws ContainerException
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $this->resolveParameter($parameter);

            if ($dependency instanceof self) {
                $dependencies[] = $this;
            } else {
                $dependencies[] = $dependency;
            }
        }

        return $dependencies;
    }

    /**
     * Resolve a single parameter.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws ContainerException
     */
    private function resolveParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type === null) {
            return $this->resolvePrimitive($parameter);
        }

        if (!$type instanceof ReflectionNamedType) {
            throw new ContainerException("Cannot resolve union/intersection type for parameter \${$parameter->getName()}");
        }

        if ($type->isBuiltin()) {
            return $this->resolvePrimitive($parameter);
        }

        return $this->get($type->getName());
    }

    /**
     * Resolve a primitive parameter.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws ContainerException
     */
    private function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ContainerException(
            "Cannot resolve parameter \${$parameter->getName()} in {$parameter->getDeclaringClass()?->getName()}"
        );
    }

    /**
     * Remove stale instances.
     *
     * @param string $abstract
     */
    private function dropStaleInstances(string $abstract): void
    {
        unset($this->bindings[$abstract], $this->resolved[$abstract]);
    }

    /**
     * Register a service provider.
     *
     * @param ServiceProviderInterface $provider
     * @return ServiceProviderInterface
     */
    public function register(ServiceProviderInterface $provider): ServiceProviderInterface
    {
        $provider->register($this);

        return $provider;
    }

    /**
     * Call a callback with dependency injection.
     *
     * @param callable $callback
     * @param array $parameters
     * @return mixed
     */
    public function call(callable $callback, array $parameters = []): mixed
    {
        if ($callback instanceof Closure) {
            $reflection = new \ReflectionFunction($callback);
            $deps = $this->resolveCallDependencies($reflection->getParameters(), $parameters);
            return $callback(...$deps);
        }

        if (is_array($callback) && count($callback) === 2) {
            [$class, $method] = $callback;
            if (is_string($class)) {
                $class = $this->get($class);
            }
            $reflection = new \ReflectionMethod($class, $method);
            $deps = $this->resolveCallDependencies($reflection->getParameters(), $parameters);
            return $class->$method(...$deps);
        }

        return $callback(...$parameters);
    }

    /**
     * Resolve dependencies for a call, merging with provided parameters.
     *
     * @param ReflectionParameter[] $reflectionParams
     * @param array $providedParams
     * @return array
     */
    private function resolveCallDependencies(array $reflectionParams, array $providedParams): array
    {
        $deps = [];

        foreach ($reflectionParams as $param) {
            $name = $param->getName();

            // Use provided parameter if exists
            if (array_key_exists($name, $providedParams)) {
                $deps[] = $providedParams[$name];
                continue;
            }

            // Try to resolve from container
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $deps[] = $this->get($type->getName());
                continue;
            }

            // Use default value if available
            if ($param->isDefaultValueAvailable()) {
                $deps[] = $param->getDefaultValue();
                continue;
            }

            // For Container type hint
            if ($type instanceof ReflectionNamedType && $type->getName() === self::class) {
                $deps[] = $this;
                continue;
            }

            throw new ContainerException("Cannot resolve parameter \${$name}");
        }

        return $deps;
    }

    /**
     * Flush the container of all bindings and resolved instances.
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->resolved = [];
        $this->extenders = [];
        $this->buildStack = [];
    }

    /**
     * Set the shared container instance.
     *
     * @param static|null $container
     */
    public static function setInstance(?self $container): void
    {
        self::$instance = $container;
    }

    /**
     * Get the shared container instance.
     *
     * @return static|null
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }
}
