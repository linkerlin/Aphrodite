<?php

declare(strict_types=1);

namespace Aphrodite\Http\Middleware;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;

/**
 * Rate limiting middleware.
 */
class RateLimitMiddleware extends \Aphrodite\Http\Middleware
{
    protected int $maxAttempts;
    protected int $decaySeconds;
    protected array $storage = [];

    public function __construct(int $maxAttempts = 60, int $decaySeconds = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    protected function handle(Request $request, callable $next): Response
    {
        return $this->process($request, $next);
    }

    public function process(Request $request, callable $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->tooManyAttempts($key)) {
            return Response::error(
                'Too many requests. Please try again later.',
                429,
                ['retry_after' => $this->availableIn($key)]
            );
        }

        $this->incrementAttempts($key);

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $key);
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return sha1($request->ip() . '|' . $request->getPath());
    }

    protected function tooManyAttempts(string $key): bool
    {
        return $this->attempts($key) >= $this->maxAttempts;
    }

    protected function attempts(string $key): int
    {
        $this->cleanOldAttempts($key);

        return $this->storage[$key]['attempts'] ?? 0;
    }

    protected function incrementAttempts(string $key): void
    {
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = [
                'attempts' => 0,
                'expires_at' => time() + $this->decaySeconds,
            ];
        }

        $this->storage[$key]['attempts']++;
    }

    protected function availableIn(string $key): int
    {
        return max(0, ($this->storage[$key]['expires_at'] ?? time()) - time());
    }

    protected function cleanOldAttempts(string $key): void
    {
        if (isset($this->storage[$key]) && $this->storage[$key]['expires_at'] < time()) {
            unset($this->storage[$key]);
        }
    }

    protected function addRateLimitHeaders(Response $response, string $key): Response
    {
        $response->setHeader('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->setHeader('X-RateLimit-Remaining', (string) max(0, $this->maxAttempts - $this->attempts($key)));
        $response->setHeader('X-RateLimit-Reset', (string) ($this->storage[$key]['expires_at'] ?? time() + $this->decaySeconds));

        return $response;
    }
}
