<?php

declare(strict_types=1);

namespace Aphrodite\Http;

/**
 * Middleware stack for processing request pipeline.
 */
class MiddlewareStack
{
    protected array $middleware = [];
    protected int $index = 0;

    /**
     * Add middleware to the stack.
     */
    public function add(MiddlewareInterface|string $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Add multiple middleware at once.
     */
    public function addMany(array $middleware): self
    {
        foreach ($middleware as $m) {
            $this->add($m);
        }
        return $this;
    }

    /**
     * Prepend middleware to the beginning of the stack.
     */
    public function prepend(MiddlewareInterface $middleware): self
    {
        array_unshift($this->middleware, $middleware);
        return $this;
    }

    /**
     * Get all middleware.
     */
    public function all(): array
    {
        return $this->middleware;
    }

    /**
     * Execute the middleware stack.
     */
    public function execute(Request $request, callable $finalHandler): Response
    {
        $this->index = 0;

        return $this->process($request, $finalHandler);
    }

    /**
     * Process the next middleware in the stack.
     */
    protected function process(Request $request, callable $finalHandler): Response
    {
        if (!isset($this->middleware[$this->index])) {
            return $finalHandler($request);
        }

        $middleware = $this->middleware[$this->index++];

        if (is_string($middleware)) {
            $middleware = new $middleware();
        }

        return $middleware->process($request, fn($req) => $this->process($req, $finalHandler));
    }
}
