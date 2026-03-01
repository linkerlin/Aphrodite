<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Router;

use PHPUnit\Framework\TestCase;
use Aphrodite\Router\AdaptiveRouter;

class AdaptiveRouterTest extends TestCase
{
    private AdaptiveRouter $router;

    protected function setUp(): void
    {
        $this->router = new AdaptiveRouter();
    }

    public function testAddAndMatchRoute(): void
    {
        $this->router->addRoute('GET', '/users', fn() => 'users');
        $match = $this->router->match('GET', '/users');
        $this->assertNotNull($match);
        $this->assertArrayHasKey('handler', $match);
    }

    public function testMatchWithParams(): void
    {
        $this->router->addRoute('GET', '/users/{id}', fn() => 'user');
        $match = $this->router->match('GET', '/users/42');
        $this->assertNotNull($match);
        $this->assertSame('42', $match['params']['id']);
    }

    public function testNoMatchReturnsNull(): void
    {
        $match = $this->router->match('GET', '/nonexistent');
        $this->assertNull($match);
    }
}
