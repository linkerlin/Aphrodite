<?php

declare(strict_types=1);

namespace Aphrodite\Router;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;

/**
 * Enhanced routing system with named routes, groups, and resource routing.
 */
class AdaptiveRouter
{
    /** @var array<int, array{method: string, path: string, regex: string, params: string[], handler: callable|array, hits: int, name?: string, middleware?: string[]}> */
    private array $routes = [];

    /** @var array<string, array{prefix: string, middleware: string[], name: string}> */
    private array $groups = [];

    private $notFoundHandler = null;

    /** @var array<string, string> */
    private array $namedRoutes = [];

    private bool $compiled = false;

    /**
     * Register a route.
     *
     * @param callable|array $handler
     */
    public function addRoute(string $method, string $path, callable|array $handler): self
    {
        [$regex, $params] = $this->compilePath($path);

        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => $path,
            'regex'   => $regex,
            'params'  => $params,
            'handler' => $handler,
            'hits'    => 0,
        ];

        $this->compiled = false;

        return $this;
    }

    /**
     * Register GET route.
     */
    public function get(string $path, callable|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register POST route.
     */
    public function post(string $path, callable|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register PUT route.
     */
    public function put(string $path, callable|array $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register PATCH route.
     */
    public function patch(string $path, callable|array $handler): self
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Register DELETE route.
     */
    public function delete(string $path, callable|array $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Register any method route.
     */
    public function any(string $path, callable|array $handler): self
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $path, $handler);
        }
        return $this;
    }

    /**
     * Register route with multiple methods.
     */
    public function mapMethods(array $methods, string $path, callable|array $handler): self
    {
        foreach ($methods as $method) {
            $this->addRoute($method, $path, $handler);
        }
        return $this;
    }

    /**
     * Name a route.
     */
    public function name(string $name): self
    {
        if (!empty($this->routes)) {
            $this->routes[count($this->routes) - 1]['name'] = $name;
            $this->namedRoutes[$name] = $name;
        }
        return $this;
    }

    /**
     * Add middleware to the last route.
     */
    public function middleware(array|string $middleware): self
    {
        if (!empty($this->routes)) {
            $index = count($this->routes) - 1;
            $middlewareList = is_array($middleware) ? $middleware : [$middleware];
            $this->routes[$index]['middleware'] = $middlewareList;
        }
        return $this;
    }

    /**
     * Create a route group.
     */
    public function group(array $config, callable $callback): void
    {
        $previousGroup = $this->groups;
        $this->groups[] = $config;

        $callback($this);

        $this->groups = $previousGroup;
    }

    /**
     * Register RESTful resource routes.
     */
    public function resource(string $name, string $controller): self
    {
        $prefix = $name;
        $controllerName = ucfirst($name) . 'Controller';

        $this->get("/{$prefix}", [$controllerName, 'index'])->name("{$name}.index");
        $this->get("/{$prefix}/create", [$controllerName, 'create'])->name("{$name}.create");
        $this->post("/{$prefix}", [$controllerName, 'store'])->name("{$name}.store");
        $this->get("/{$prefix}/{{$name}_id}", [$controllerName, 'show'])->name("{$name}.show");
        $this->get("/{$prefix}/{{$name}_id}/edit", [$controllerName, 'edit'])->name("{$name}.edit");
        $this->put("/{$prefix}/{{$name}_id}", [$controllerName, 'update'])->name("{$name}.update");
        $this->delete("/{$prefix}/{{$name}_id}", [$controllerName, 'destroy'])->name("{$name}.destroy");

        return $this;
    }

    /**
     * Set not found handler.
     */
    public function notFound(callable $handler): self
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Get URL by route name.
     */
    public function getUrl(string $name, array $params = []): ?string
    {
        foreach ($this->routes as $route) {
            if (($route['name'] ?? null) === $name) {
                $path = $route['path'];
                foreach ($params as $key => $value) {
                    $path = str_replace("{{$key}}", (string) $value, $path);
                }
                return $path;
            }
        }
        return null;
    }

    /**
     * Match an incoming request against registered routes.
     *
     * @return array{handler: callable|array, params: array<string, string>, middleware?: string[]}|null
     */
    public function match(string $method, string $path): ?array
    {
        $method = strtoupper($method);
        $path = parse_url($path, PHP_URL_PATH);

        if (!$this->compiled) {
            $this->compile();
        }

        foreach ($this->routes as &$route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                $route['hits']++;

                $params = [];
                foreach ($route['params'] as $index => $name) {
                    $params[$name] = $matches[$index + 1] ?? '';
                }

                return [
                    'handler'   => $route['handler'],
                    'params'    => $params,
                    'middleware' => $route['middleware'] ?? [],
                ];
            }
        }

        return null;
    }

    /**
     * Handle a request and return response.
     */
    public function handle(Request $request): Response
    {
        $result = $this->match($request->getMethod(), $request->getPath());

        if ($result === null) {
            if ($this->notFoundHandler !== null) {
                return ($this->notFoundHandler)($request);
            }
            return Response::notFound('Route not found');
        }

        foreach ($result['middleware'] ?? [] as $middleware) {
            if ($middleware instanceof \Closure) {
                $request = $request->setAttribute('route_params', $result['params']);
                $result['handler'] = fn($req) => $middleware($req, fn($req) => $this->executeHandler($result['handler'], $req, $result['params']));
            } elseif (class_exists($middleware)) {
                $instance = new $middleware();
                $request = $request->setAttribute('route_params', $result['params']);
                $result['handler'] = fn($req) => $instance->process($req, fn($req) => $this->executeHandler($result['handler'], $req, $result['params']));
            }
        }

        return $this->executeHandler($result['handler'], $request, $result['params']);
    }

    /**
     * Execute route handler.
     */
    protected function executeHandler(callable|array $handler, Request $request, array $params): Response
    {
        if (is_callable($handler)) {
            return $handler($request, $params);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controller, $method] = $handler;

            if (is_string($controller) && class_exists($controller)) {
                $controller = new $controller();
            }

            if (method_exists($controller, $method)) {
                return $controller->$method($request, $params);
            }
        }

        return Response::error('Handler not found', 500);
    }

    /**
     * Auto-generate RESTful routes from an intent array.
     *
     * @param array{entity?: string, operations?: string[]} $intent
     */
    public function generateFromIntent(array $intent): void
    {
        $entity     = strtolower($intent['entity'] ?? 'resource');
        $plural     = $entity . 's';
        $operations = $intent['operations'] ?? ['list', 'read', 'create', 'update', 'delete'];

        $map = [
            'list'   => ['GET',    "/{$plural}"],
            'create' => ['POST',   "/{$plural}"],
            'read'   => ['GET',    "/{$plural}/{id}"],
            'update' => ['PUT',    "/{$plural}/{id}"],
            'delete' => ['DELETE', "/{$plural}/{id}"],
        ];

        foreach ($operations as $op) {
            if (isset($map[$op])) {
                [$method, $path] = $map[$op];
                $this->addRoute($method, $path, [ucfirst($entity) . 'Controller', $op]);
            }
        }
    }

    /**
     * Sort routes by hit frequency (most-used first) for faster matching.
     */
    public function optimize(): void
    {
        usort($this->routes, static fn($a, $b) => $b['hits'] <=> $a['hits']);
        $this->compile();
    }

    /**
     * Pre-compile all routes for faster matching.
     */
    protected function compile(): void
    {
        foreach ($this->routes as &$route) {
            $prefix = '';
            foreach ($this->groups as $group) {
                $prefix .= $group['prefix'] ?? '';
            }

            if ($prefix) {
                $route['path'] = $prefix . $route['path'];
                [$route['regex'], $route['params']] = $this->compilePath($route['path']);
            }
        }

        $this->compiled = true;
    }

    /**
     * Compile a path pattern into a regex and extract parameter names.
     *
     * @return array{string, string[]}
     */
    private function compilePath(string $path): array
    {
        $params = [];

        $regex = preg_replace_callback(
            '/\{(\w+)\}/',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                return '([^/]+)';
            },
            $path
        ) ?? $path;

        return [
            '#^' . $regex . '$#',
            $params,
        ];
    }

    /**
     * Get all registered routes.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get route count.
     */
    public function count(): int
    {
        return count($this->routes);
    }
}
