<?php

declare(strict_types=1);

namespace Aphrodite\Router;

/**
 * Adaptive routing system that supports dynamic parameters and auto-generation
 * from intent arrays.
 */
class AdaptiveRouter
{
    /** @var array<int, array{method: string, path: string, regex: string, params: string[], handler: callable|array, hits: int}> */
    private array $routes = [];

    /**
     * Register a route.
     *
     * @param callable|array $handler
     */
    public function addRoute(string $method, string $path, callable|array $handler): void
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
    }

    /**
     * Match an incoming request against registered routes.
     *
     * @return array{handler: callable|array, params: array<string, string>}|null
     */
    public function match(string $method, string $path): ?array
    {
        $method = strtoupper($method);

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
                    'handler' => $route['handler'],
                    'params'  => $params,
                ];
            }
        }

        return null;
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
}
