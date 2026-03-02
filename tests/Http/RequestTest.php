<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Http;

use Aphrodite\Http\Request;
use Aphrodite\Http\Response;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testCreateRequestFromSuperglobals(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users?name=john';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $_GET = ['name' => 'john'];
        $_POST = ['email' => 'john@example.com'];

        $request = Request::capture();

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/users?name=john', $request->getUri());
        $this->assertEquals('/users', $request->getPath());
        $this->assertEquals('john', $request->get('name'));
        $this->assertEquals('john@example.com', $request->post('email'));
    }

    public function testGetQueryParameters(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test?a=1&b=2'];
        $_GET = ['a' => '1', 'b' => '2'];

        $request = Request::capture();

        $this->assertEquals('1', $request->get('a'));
        $this->assertEquals('2', $request->get('b'));
        $this->assertEquals(['a' => '1', 'b' => '2'], $request->getQuery());
    }

    public function testInputMergesQueryAndPost(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test?from=get'];
        $_GET = ['from' => 'get'];
        $_POST = ['from' => 'post', 'extra' => 'value'];

        $request = Request::capture();

        $this->assertEquals('post', $request->input('from'));
        $this->assertEquals('value', $request->input('extra'));
    }

    public function testAllReturnsMergedInput(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'];
        $_GET = ['query' => 'value'];
        $_POST = ['post' => 'value'];

        $request = Request::capture();
        $all = $request->all();

        $this->assertArrayHasKey('query', $all);
        $this->assertArrayHasKey('post', $all);
    }

    public function testHasChecksBothQueryAndPost(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'];
        $_GET = ['query' => '1'];
        $_POST = ['post' => '1'];

        $request = Request::capture();

        $this->assertTrue($request->has('query'));
        $this->assertTrue($request->has('post'));
        $this->assertFalse($request->has('nonexistent'));
    }

    public function testOnlyReturnsSpecifiedKeys(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'];
        $_GET = ['a' => '1', 'b' => '2', 'c' => '3'];
        $_POST = [];

        $request = Request::capture();

        $result = $request->only(['a', 'c']);

        $this->assertEquals(['a' => '1', 'c' => '3'], $result);
        $this->assertArrayNotHasKey('b', $result);
    }

    public function testExceptExcludesSpecifiedKeys(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'];
        $_GET = ['a' => '1', 'b' => '2', 'c' => '3'];
        $_POST = [];

        $request = Request::capture();

        $result = $request->except(['b']);

        $this->assertEquals(['a' => '1', 'c' => '3'], $result);
        $this->assertArrayNotHasKey('b', $result);
    }

    public function testRequestMethods(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Request::capture();

        $this->assertTrue($request->isPost());
        $this->assertFalse($request->isGet());
        $this->assertFalse($request->isPut());
        $this->assertFalse($request->isDelete());
    }

    public function testGetHeaders(): void
    {
        $request = new Request(
            'GET',
            '/',
            [],
            [],
            ['authorization' => 'Bearer token123', 'accept' => 'application/json'],
            []
        );

        $this->assertEquals('Bearer token123', $request->header('authorization'));
        $this->assertEquals('application/json', $request->header('accept'));
    }

    public function testGetJsonBody(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'];
        
        $request = new Request(
            'POST',
            '/',
            [],
            [],
            ['content-type' => 'application/json'],
            [],
            json_encode(['name' => 'John', 'age' => 30])
        );

        $json = $request->getJson();

        $this->assertIsArray($json);
        $this->assertEquals('John', $json['name']);
        $this->assertEquals(30, $json['age']);
    }

    public function testGetClientIp(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'];
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        $request = Request::capture();

        $this->assertEquals('192.168.1.1', $request->ip());
    }

    public function testAttributes(): void
    {
        $request = new Request();

        $request->setAttribute('user_id', 123);
        $request->setAttribute('role', 'admin');

        $this->assertEquals(123, $request->getAttribute('user_id'));
        $this->assertEquals('admin', $request->getAttribute('role'));
        $this->assertNull($request->getAttribute('nonexistent'));
        $this->assertEquals('default', $request->getAttribute('nonexistent', 'default'));
    }

    public function testDefaultValues(): void
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'];

        $request = Request::capture();

        $this->assertEquals('default', $request->get('nonexistent', 'default'));
        $this->assertEquals('default', $request->post('nonexistent', 'default'));
    }
}
