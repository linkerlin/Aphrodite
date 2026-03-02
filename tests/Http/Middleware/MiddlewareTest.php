<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Http\Middleware;

require_once __DIR__ . '/../../../src/Http/MiddlewareInterface.php';
require_once __DIR__ . '/../../../src/Http/Request.php';
require_once __DIR__ . '/../../../src/Http/Response.php';
require_once __DIR__ . '/../../../src/Http/Middleware/CorsMiddleware.php';
require_once __DIR__ . '/../../../src/Http/Middleware/LoggingMiddleware.php';
require_once __DIR__ . '/../../../src/Http/Middleware/RateLimitMiddleware.php';

use Aphrodite\Http\Middleware\CorsMiddleware;
use Aphrodite\Http\Middleware\LoggingMiddleware;
use Aphrodite\Http\Middleware\RateLimitMiddleware;
use Aphrodite\Http\Request;
use Aphrodite\Http\Response;
use PHPUnit\Framework\TestCase;

class CorsMiddlewareTest extends TestCase
{
    public function testAllowsWildcardOrigin(): void
    {
        $middleware = new CorsMiddleware();
        
        $request = new Request('GET', '/', [], [], ['origin' => 'http://example.com']);
        $next = fn($req) => Response::success();
        
        $response = $middleware->process($request, $next);
        
        $this->assertEquals('http://example.com', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testAllowsSpecificOrigin(): void
    {
        $middleware = new CorsMiddleware([
            'allowed_origins' => ['http://trusted.com'],
        ]);
        
        $request = new Request('GET', '/', [], [], ['origin' => 'http://trusted.com']);
        $next = fn($req) => Response::success();
        
        $response = $middleware->process($request, $next);
        
        $this->assertEquals('http://trusted.com', $response->getHeader('Access-Control-Allow-Origin'));
    }

    public function testRejectsUntrustedOrigin(): void
    {
        $middleware = new CorsMiddleware([
            'allowed_origins' => ['http://trusted.com'],
        ]);
        
        $request = new Request('GET', '/', [], [], ['origin' => 'http://evil.com']);
        $next = fn($req) => Response::success();
        
        $response = $middleware->process($request, $next);
        
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testHandlesPreflightRequest(): void
    {
        $middleware = new CorsMiddleware();
        
        $request = new Request('OPTIONS', '/', [], [], [
            'origin' => 'http://example.com',
            'access-control-request-method' => 'POST',
        ]);
        $next = fn($req) => Response::success();
        
        $response = $middleware->process($request, $next);
        
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertStringContainsString('GET', $response->getHeader('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('POST', $response->getHeader('Access-Control-Allow-Methods'));
    }

    public function testSetsExposedHeaders(): void
    {
        $middleware = new CorsMiddleware([
            'exposed_headers' => ['X-Custom-Header'],
        ]);
        
        $request = new Request('GET', '/', [], [], ['Origin' => 'http://example.com']);
        $next = fn($req) => Response::success();
        
        $response = $middleware->process($request, $next);
        
        $this->assertStringContainsString('X-Custom-Header', $response->getHeader('Access-Control-Expose-Headers'));
    }

    public function testSetsCredentialsHeader(): void
    {
        $middleware = new CorsMiddleware([
            'supports_credentials' => true,
        ]);
        
        $request = new Request('GET', '/', [], [], ['Origin' => 'http://example.com']);
        $next = fn($req) => Response::success();
        
        $response = $middleware->process($request, $next);
        
        $this->assertEquals('true', $response->getHeader('Access-Control-Allow-Credentials'));
    }
}

class LoggingMiddlewareTest extends TestCase
{
    public function testLogsRequest(): void
    {
        $logs = [];
        
        $logger = function($message) use (&$logs) {
            $logs[] = $message;
        };
        
        $middleware = new LoggingMiddleware($logger);
        
        $request = new Request('GET', '/api/users');
        $next = fn($req) => Response::success();
        
        $middleware->process($request, $next);
        
        $this->assertCount(2, $logs); // Request and response
        $this->assertStringContainsString('GET', $logs[0]);
        $this->assertStringContainsString('/api/users', $logs[0]);
    }

    public function testLogsResponseStatus(): void
    {
        $logs = [];
        
        $logger = function($message) use (&$logs) {
            $logs[] = $message;
        };
        
        $middleware = new LoggingMiddleware($logger);
        
        $request = new Request('POST', '/api/users');
        $next = fn($req) => Response::make('Created', 201);
        
        $middleware->process($request, $next);
        
        $this->assertStringContainsString('201', $logs[1]);
    }
}

class RateLimitMiddlewareTest extends TestCase
{
    public function testAllowsRequestsUnderLimit(): void
    {
        $middleware = new RateLimitMiddleware(10, 60);
        
        $request = new Request('GET', '/api/test');
        $next = fn($req) => Response::success();
        
        $response = $middleware->process($request, $next);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('9', $response->getHeader('X-RateLimit-Remaining'));
    }

    public function testBlocksRequestsOverLimit(): void
    {
        $middleware = new RateLimitMiddleware(2, 60);
        
        $request = new Request('GET', '/api/test');
        
        // First request
        $middleware->process($request, fn($req) => Response::success());
        
        // Second request
        $middleware->process($request, fn($req) => Response::success());
        
        // Third request should be blocked
        $response = $middleware->process($request, fn($req) => Response::success());
        
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals('0', $response->getHeader('X-RateLimit-Remaining'));
    }

    public function testSetsRateLimitHeaders(): void
    {
        $middleware = new RateLimitMiddleware(5, 60);
        
        $request = new Request('GET', '/api/test');
        $next = fn($req) => Response::success();
        
        $response = $middleware->process($request, $next);
        
        $this->assertEquals('5', $response->getHeader('X-RateLimit-Limit'));
        $this->assertNotEmpty($response->getHeader('X-RateLimit-Reset'));
    }

    public function testDifferentPathsHaveSeparateLimits(): void
    {
        $middleware = new RateLimitMiddleware(1, 60);
        
        $request1 = new Request('GET', '/api/path1');
        $request2 = new Request('GET', '/api/path2');
        
        // First request to path1
        $middleware->process($request1, fn($req) => Response::success());
        
        // Second request to different path should succeed (different key)
        $response = $middleware->process($request2, fn($req) => Response::success());
        
        $this->assertEquals(200, $response->getStatusCode());
    }
}
