<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Http;

use Aphrodite\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testCreateBasicResponse(): void
    {
        $response = new Response('Hello World', 200);

        $this->assertEquals('Hello World', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testJsonResponse(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $response = Response::json($data, 200);

        $this->assertEquals(json_encode($data), $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
    }

    public function testSuccessResponse(): void
    {
        $response = Response::success(['id' => 1], 'Created successfully', 201);

        $json = json_decode($response->getContent(), true);

        $this->assertTrue($json['success']);
        $this->assertEquals('Created successfully', $json['message']);
        $this->assertEquals(['id' => 1], $json['data']);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testErrorResponse(): void
    {
        $response = Response::error('Validation failed', 422);

        $json = json_decode($response->getContent(), true);

        $this->assertFalse($json['success']);
        $this->assertEquals('Validation failed', $json['message']);
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testNotFoundResponse(): void
    {
        $response = Response::notFound();

        $this->assertEquals(404, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
    }

    public function testUnauthorizedResponse(): void
    {
        $response = Response::unauthorized();

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testForbiddenResponse(): void
    {
        $response = Response::forbidden();

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testValidationErrorResponse(): void
    {
        $errors = ['email' => 'Invalid email format'];
        $response = Response::validationError($errors);

        $this->assertEquals(422, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $json);
    }

    public function testServerErrorResponse(): void
    {
        $response = Response::serverError();

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testRedirectResponse(): void
    {
        $response = Response::redirect('/users', 302);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/users', $response->getHeader('Location'));
    }

    public function testSetContent(): void
    {
        $response = new Response('Initial');
        $response->setContent('Updated');

        $this->assertEquals('Updated', $response->getContent());
    }

    public function testSetStatusCode(): void
    {
        $response = new Response('Test', 200);
        $response->setStatusCode(404);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testSetAndGetHeaders(): void
    {
        $response = new Response('Test');
        $response->setHeader('X-Custom', 'value');

        $this->assertEquals('value', $response->getHeader('X-Custom'));
        $this->assertEquals(['X-Custom' => 'value'], $response->getHeaders());
    }

    public function testPrepareOutput(): void
    {
        $response = new Response('Hello', 200, ['Content-Type' => 'text/plain']);
        
        $output = $response->prepare();

        $this->assertStringContainsString('Content-Type: text/plain', $output);
        $this->assertStringContainsString('Hello', $output);
    }

    public function testHttpConstants(): void
    {
        $this->assertEquals(200, Response::HTTP_OK);
        $this->assertEquals(201, Response::HTTP_CREATED);
        $this->assertEquals(204, Response::HTTP_NO_CONTENT);
        $this->assertEquals(400, Response::HTTP_BAD_REQUEST);
        $this->assertEquals(401, Response::HTTP_UNAUTHORIZED);
        $this->assertEquals(403, Response::HTTP_FORBIDDEN);
        $this->assertEquals(404, Response::HTTP_NOT_FOUND);
        $this->assertEquals(422, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertEquals(500, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
