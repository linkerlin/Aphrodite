<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Router;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;
use Aphrodite\Router\AdaptiveRouter;
use PHPUnit\Framework\TestCase;

class AdaptiveRouterIntegrationTest extends TestCase
{
    private AdaptiveRouter $router;

    protected function setUp(): void
    {
        $this->router = new AdaptiveRouter();
    }

    public function testGetRoute(): void
    {
        $this->router->get('/users', function ($req, $params) {
            return Response::success(['users' => []]);
        });

        $request = new Request('GET', '/users');
        $response = $this->router->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testPostRoute(): void
    {
        $this->router->post('/users', function ($req, $params) {
            return Response::success(['created' => true], 'User created', 201);
        });

        $request = new Request('POST', '/users');
        $response = $this->router->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testRouteWithParameters(): void
    {
        $this->router->get('/users/{id}', function ($req, $params) {
            return Response::success(['id' => $params['id']]);
        });

        $request = new Request('GET', '/users/123');
        $response = $this->router->handle($request);

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('123', $data['data']['id']);
    }

    public function testNamedRoute(): void
    {
        $this->router->get('/users', function ($req, $params) {
            return Response::success();
        })->name('users.index');

        $url = $this->router->getUrl('users.index');

        $this->assertEquals('/users', $url);
    }

    public function testNamedRouteWithParams(): void
    {
        $this->router->get('/users/{id}', function ($req, $params) {
            return Response::success();
        })->name('users.show');

        $url = $this->router->getUrl('users.show', ['id' => 42]);

        $this->assertEquals('/users/42', $url);
    }

    public function testResourceRoutes(): void
    {
        $this->router->resource('posts', 'PostController');

        // Test index
        $this->assertNotNull($this->router->match('GET', '/posts'));
        
        // Test show
        $this->assertNotNull($this->router->match('GET', '/posts/1'));
        
        // Test store
        $this->assertNotNull($this->router->match('POST', '/posts'));
        
        // Test update
        $this->assertNotNull($this->router->match('PUT', '/posts/1'));
        
        // Test destroy
        $this->assertNotNull($this->router->match('DELETE', '/posts/1'));
    }

    public function testNotFound(): void
    {
        $this->router->notFound(function ($req) {
            return Response::notFound('Custom not found');
        });

        $request = new Request('GET', '/nonexistent');
        $response = $this->router->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testMatchMultipleMethods(): void
    {
        $this->router->mapMethods(['GET', 'POST'], '/api', function ($req, $params) {
            return Response::success();
        });

        $this->assertNotNull($this->router->match('GET', '/api'));
        $this->assertNotNull($this->router->match('POST', '/api'));
        $this->assertNull($this->router->match('PUT', '/api'));
    }

    public function testAnyRoute(): void
    {
        $this->router->any('/test', function ($req, $params) {
            return Response::success();
        });

        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $this->assertNotNull($this->router->match($method, '/test'));
        }
    }

    public function testRouteWithMiddleware(): void
    {
        $executed = false;
        
        $this->router->get('/api', function ($req, $params) use (&$executed) {
            $executed = true;
            return Response::success();
        })->middleware([function ($req, $next) {
            return $next($req);
        }]);

        $request = new Request('GET', '/api');
        $this->router->handle($request);

        $this->assertTrue($executed);
    }

    public function testQueryStringPreserved(): void
    {
        $this->router->get('/search', function ($req, $params) {
            return Response::success(['query' => $req->get('q')]);
        });

        $request = new Request('GET', '/search?q=hello');
        $response = $this->router->handle($request);

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('hello', $data['data']['query']);
    }

    public function testGenerateFromIntent(): void
    {
        $intent = [
            'entity' => 'User',
            'operations' => ['list', 'read', 'create', 'update', 'delete']
        ];

        $this->router->generateFromIntent($intent);

        $this->assertNotNull($this->router->match('GET', '/users'));
        $this->assertNotNull($this->router->match('GET', '/users/1'));
        $this->assertNotNull($this->router->match('POST', '/users'));
    }

    public function testRouteCount(): void
    {
        $this->router->get('/a', function () {});
        $this->router->get('/b', function () {});
        $this->router->post('/c', function () {});

        $this->assertEquals(3, $this->router->count());
    }

    public function testGetRoutes(): void
    {
        $this->router->get('/test', function () {});

        $routes = $this->router->getRoutes();

        $this->assertIsArray($routes);
        $this->assertCount(1, $routes);
    }
}
