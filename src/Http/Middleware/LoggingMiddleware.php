<?php

declare(strict_types=1);

namespace Aphrodite\Http\Middleware;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;

/**
 * Logging middleware for recording requests and responses.
 */
class LoggingMiddleware extends \Aphrodite\Http\Middleware
{
    protected $logger;

    public function __construct(?callable $logger = null)
    {
        $this->logger = $logger ?? function (string $message) {
            error_log($message);
        };
    }

    protected function handle(Request $request, callable $next): Response
    {
        $startTime = microtime(true);
        $method = $request->getMethod();
        $uri = $request->getUri();
        $ip = $request->ip();

        $this->log(">> {$method} {$uri} from {$ip}");

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $status = $response->getStatusCode();

        $this->log("<< {$method} {$uri} - {$status} ({$duration}ms)");

        return $response;
    }

    public function process(Request $request, callable $next): Response
    {
        return $this->handle($request, $next);
    }

    protected function log(string $message): void
    {
        ($this->logger)($message);
    }
}
