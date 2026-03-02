<?php

declare(strict_types=1);

namespace Aphrodite\Http;

/**
 * Middleware interface.
 */
interface MiddlewareInterface
{
    /**
     * Process the request and return response.
     */
    public function process(Request $request, callable $next): Response;
}

/**
 * Base middleware class.
 */
abstract class Middleware implements MiddlewareInterface
{
    /**
     * Handle middleware processing.
     */
    abstract protected function handle(Request $request, callable $next): Response;
}
