<?php

declare(strict_types=1);

namespace Aphrodite\Http\Middleware;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;

/**
 * CORS middleware for handling cross-origin requests.
 */
class CorsMiddleware extends \Aphrodite\Http\Middleware
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'exposed_headers' => [],
            'max_age' => 86400,
            'supports_credentials' => false,
        ], $config);
    }

    protected function handle(Request $request, callable $next): Response
    {
        $origin = $request->header('Origin', '*');

        if (in_array('*', $this->config['allowed_origins']) || in_array($origin, $this->config['allowed_origins'])) {
            $response = $next($request);

            $response->setHeader('Access-Control-Allow-Origin', $origin);

            if ($this->config['supports_credentials']) {
                $response->setHeader('Access-Control-Allow-Credentials', 'true');
            }

            if (!empty($this->config['exposed_headers'])) {
                $response->setHeader('Access-Control-Expose-Headers', implode(', ', $this->config['exposed_headers']));
            }

            return $response;
        }

        return Response::error('Origin not allowed', 403);
    }

    public function process(Request $request, callable $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflight($request);
        }

        return $this->handle($request, $next);
    }

    protected function handlePreflight(Request $request): Response
    {
        $origin = $request->header('Origin', '*');

        $response = Response::make('', 204);
        $response->setHeader('Access-Control-Allow-Origin', $origin);
        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->config['allowed_methods']));
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->config['allowed_headers']));
        $response->setHeader('Access-Control-Max-Age', (string) $this->config['max_age']);

        if ($this->config['supports_credentials']) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
