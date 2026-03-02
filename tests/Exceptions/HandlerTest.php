<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Exceptions;

use Aphrodite\Container\Container;
use Aphrodite\Exceptions\AuthenticationException;
use Aphrodite\Exceptions\AuthorizationException;
use Aphrodite\Exceptions\EntityNotFoundException;
use Aphrodite\Exceptions\Handler;
use Aphrodite\Exceptions\RouteNotFoundException;
use Aphrodite\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    private Container $container;
    private Handler $handler;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->handler = new Handler($this->container, false);
    }

    public function testHandlerCanBeCreated(): void
    {
        $this->assertInstanceOf(Handler::class, $this->handler);
    }

    public function testHandlerCanBeCreatedFromContainer(): void
    {
        $handler = Handler::create($this->container);

        $this->assertInstanceOf(Handler::class, $handler);
    }

    public function testHandlerCanBeCreatedWithDebugMode(): void
    {
        $container = new Container();
        $container->instance('app.debug', true);

        $handler = Handler::create($container);

        $this->assertInstanceOf(Handler::class, $handler);
    }

    public function testSetDebug(): void
    {
        $result = $this->handler->setDebug(true);

        $this->assertSame($this->handler, $result);
    }

    public function testDontReport(): void
    {
        $result = $this->handler->dontReport(\RuntimeException::class);

        $this->assertSame($this->handler, $result);
    }

    public function testReportDoesNotThrowWithoutLogger(): void
    {
        $exception = new \RuntimeException('Test');

        $this->handler->report($exception);

        $this->assertTrue(true);
    }

    public function testReportSkipsValidationException(): void
    {
        $exception = ValidationException::forField('name', 'Required');

        $this->handler->report($exception);

        $this->assertTrue(true);
    }

    public function testReportSkipsEntityNotFoundException(): void
    {
        $exception = EntityNotFoundException::forEntity('User', 1);

        $this->handler->report($exception);

        $this->assertTrue(true);
    }

    public function testReportSkipsRouteNotFoundException(): void
    {
        $exception = RouteNotFoundException::forPath('/test');

        $this->handler->report($exception);

        $this->assertTrue(true);
    }

    public function testReportSkipsAuthenticationException(): void
    {
        $exception = new AuthenticationException();

        $this->handler->report($exception);

        $this->assertTrue(true);
    }

    public function testReportSkipsAuthorizationException(): void
    {
        $exception = new AuthorizationException();

        $this->handler->report($exception);

        $this->assertTrue(true);
    }

    public function testRenderValidationExceptionReturnsJson(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_SERVER['REQUEST_URI'] = '/api/test';

        $exception = ValidationException::forField('email', 'Invalid');
        $output = $this->handler->render($exception);

        $data = json_decode($output, true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testRenderEntityNotFoundExceptionReturnsJson(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $exception = EntityNotFoundException::forEntity('User', 1);
        $output = $this->handler->render($exception);

        $data = json_decode($output, true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('User', $data['message']);
    }

    public function testRenderAuthenticationExceptionReturnsJson(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $exception = new AuthenticationException('Token expired');
        $output = $this->handler->render($exception);

        $data = json_decode($output, true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Token expired', $data['message']);
    }

    public function testRenderAuthorizationExceptionReturnsJson(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $exception = new AuthorizationException('Access denied');
        $output = $this->handler->render($exception);

        $data = json_decode($output, true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Access denied', $data['message']);
    }

    public function testRenderGenericExceptionReturnsJson(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $exception = new \RuntimeException('Something went wrong');
        $output = $this->handler->render($exception);

        $data = json_decode($output, true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Something went wrong', $data['message']);
    }

    public function testRenderWithDebugIncludesTrace(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $handler = new Handler($this->container, true);
        $exception = new \RuntimeException('Debug test');
        $output = $handler->render($exception);

        $data = json_decode($output, true);

        $this->assertArrayHasKey('debug', $data);
        $this->assertEquals('RuntimeException', $data['debug']['exception']);
    }

    public function testRenderWithoutDebugExcludesTrace(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $exception = new \RuntimeException('Production test');
        $output = $this->handler->render($exception);

        $data = json_decode($output, true);

        $this->assertArrayNotHasKey('debug', $data);
    }

    public function testRenderHtmlReturnsHtml(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_SERVER['REQUEST_URI'] = '/page';

        $exception = new \RuntimeException('HTML test');
        $output = $this->handler->render($exception);

        $this->assertStringContainsString('<!DOCTYPE html>', $output);
        $this->assertStringContainsString('500', $output);
    }

    public function testRenderHtmlWithDebugShowsDetails(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_SERVER['REQUEST_URI'] = '/page';

        $handler = new Handler($this->container, true);
        $exception = new \RuntimeException('Debug HTML');
        $output = $handler->render($exception);

        $this->assertStringContainsString('RuntimeException', $output);
        $this->assertStringContainsString('Debug HTML', $output);
    }

    public function testRenderHtmlWithoutDebugHidesDetails(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_SERVER['REQUEST_URI'] = '/page';

        $exception = new \RuntimeException('Secret error');
        $output = $this->handler->render($exception);

        $this->assertStringNotContainsString('Secret error', $output);
        $this->assertStringNotContainsString('RuntimeException', $output);
    }

    public function testDetectsApiRequestFromAcceptHeader(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_SERVER['REQUEST_URI'] = '/page';

        $exception = new \RuntimeException('Test');
        $output = $this->handler->render($exception);

        $this->assertJson($output);
    }

    public function testDetectsApiRequestFromUri(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_SERVER['REQUEST_URI'] = '/api/users';

        $exception = new \RuntimeException('Test');
        $output = $this->handler->render($exception);

        $this->assertJson($output);
    }

    public function testDetectsApiRequestFromContentType(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['REQUEST_URI'] = '/page';

        $exception = new \RuntimeException('Test');
        $output = $this->handler->render($exception);

        $this->assertJson($output);
    }
}
