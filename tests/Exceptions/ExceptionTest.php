<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Exceptions;

use Aphrodite\Exceptions\AphroditeException;
use Aphrodite\Exceptions\AuthenticationException;
use Aphrodite\Exceptions\AuthorizationException;
use Aphrodite\Exceptions\EntityNotFoundException;
use Aphrodite\Exceptions\RouteNotFoundException;
use Aphrodite\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    // === AphroditeException Tests ===

    public function testAphroditeExceptionCreatesWithMessage(): void
    {
        $exception = new AphroditeException('Test message');

        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testAphroditeExceptionCreatesWithCode(): void
    {
        $exception = new AphroditeException('Test', 500);

        $this->assertEquals(500, $exception->getCode());
    }

    public function testAphroditeExceptionCreatesWithContext(): void
    {
        $context = ['key' => 'value', 'number' => 42];
        $exception = new AphroditeException('Test', 0, null, $context);

        $this->assertEquals($context, $exception->getContext());
    }

    public function testAphroditeExceptionSetContext(): void
    {
        $exception = new AphroditeException('Test');
        $exception->setContext(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $exception->getContext());
    }

    public function testAphroditeExceptionAddContext(): void
    {
        $exception = new AphroditeException('Test', 0, null, ['a' => 1]);
        $exception->addContext('b', 2);

        $this->assertEquals(['a' => 1, 'b' => 2], $exception->getContext());
    }

    public function testAphroditeExceptionWithContextFactory(): void
    {
        $exception = AphroditeException::withContext('Test', ['x' => 'y']);

        $this->assertEquals('Test', $exception->getMessage());
        $this->assertEquals(['x' => 'y'], $exception->getContext());
    }

    // === ValidationException Tests ===

    public function testValidationExceptionCreatesWithErrors(): void
    {
        $errors = ['name' => ['Name is required', 'Name must be at least 3 characters']];
        $exception = new ValidationException($errors);

        $this->assertEquals($errors, $exception->getErrors());
        $this->assertEquals(422, $exception->getCode());
    }

    public function testValidationExceptionGetErrorsForField(): void
    {
        $errors = ['email' => ['Invalid email']];
        $exception = new ValidationException($errors);

        $this->assertEquals(['Invalid email'], $exception->getErrorsForField('email'));
        $this->assertEquals([], $exception->getErrorsForField('name'));
    }

    public function testValidationExceptionHasErrorsForField(): void
    {
        $exception = new ValidationException(['name' => ['Required']]);

        $this->assertTrue($exception->hasErrorsForField('name'));
        $this->assertFalse($exception->hasErrorsForField('email'));
    }

    public function testValidationExceptionGetFirstErrorForField(): void
    {
        $exception = new ValidationException(['name' => ['Error 1', 'Error 2']]);

        $this->assertEquals('Error 1', $exception->getFirstErrorForField('name'));
        $this->assertNull($exception->getFirstErrorForField('nonexistent'));
    }

    public function testValidationExceptionForFieldFactory(): void
    {
        $exception = ValidationException::forField('email', 'Invalid email format');

        $this->assertEquals(['email' => ['Invalid email format']], $exception->getErrors());
    }

    public function testValidationExceptionFromErrorsFactory(): void
    {
        $errors = [
            'name' => ['Required'],
            'email' => ['Invalid format', 'Already exists'],
        ];
        $exception = ValidationException::fromErrors($errors);

        $this->assertEquals($errors, $exception->getErrors());
    }

    // === EntityNotFoundException Tests ===

    public function testEntityNotFoundExceptionCreatesWithEntityType(): void
    {
        $exception = new EntityNotFoundException('User', 123);

        $this->assertEquals('User', $exception->getEntityType());
        $this->assertEquals(123, $exception->getIdentifier());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testEntityNotFoundExceptionGeneratesMessage(): void
    {
        $exception = new EntityNotFoundException('User', 123);

        $this->assertStringContainsString('User', $exception->getMessage());
        $this->assertStringContainsString('123', $exception->getMessage());
    }

    public function testEntityNotFoundExceptionForEntityFactory(): void
    {
        $exception = EntityNotFoundException::forEntity('Post', 'abc-123');

        $this->assertEquals('Post', $exception->getEntityType());
        $this->assertEquals('abc-123', $exception->getIdentifier());
    }

    public function testEntityNotFoundExceptionForModelFactory(): void
    {
        $exception = EntityNotFoundException::forModel('App\\Models\\Product', 456);

        $this->assertEquals('Product', $exception->getEntityType());
        $this->assertEquals(456, $exception->getIdentifier());
    }

    // === RouteNotFoundException Tests ===

    public function testRouteNotFoundExceptionCreatesWithPath(): void
    {
        $exception = new RouteNotFoundException('/api/users', 'POST');

        $this->assertEquals('/api/users', $exception->getPath());
        $this->assertEquals('POST', $exception->getMethod());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testRouteNotFoundExceptionGeneratesMessage(): void
    {
        $exception = new RouteNotFoundException('/api/users', 'POST');

        $this->assertStringContainsString('/api/users', $exception->getMessage());
        $this->assertStringContainsString('POST', $exception->getMessage());
    }

    public function testRouteNotFoundExceptionForPathFactory(): void
    {
        $exception = RouteNotFoundException::forPath('/test/path', 'DELETE');

        $this->assertEquals('/test/path', $exception->getPath());
        $this->assertEquals('DELETE', $exception->getMethod());
    }

    // === AuthenticationException Tests ===

    public function testAuthenticationExceptionCreatesWithDefaults(): void
    {
        $exception = new AuthenticationException();

        $this->assertEquals('Unauthenticated', $exception->getMessage());
        $this->assertEquals(401, $exception->getCode());
    }

    public function testAuthenticationExceptionCreatesWithGuard(): void
    {
        $exception = new AuthenticationException('Token expired', 'api');

        $this->assertEquals('Token expired', $exception->getMessage());
        $this->assertEquals('api', $exception->getGuard());
    }

    public function testAuthenticationExceptionForGuardFactory(): void
    {
        $exception = AuthenticationException::forGuard('web');

        $this->assertEquals('web', $exception->getGuard());
        $this->assertStringContainsString('web', $exception->getMessage());
    }

    // === AuthorizationException Tests ===

    public function testAuthorizationExceptionCreatesWithDefaults(): void
    {
        $exception = new AuthorizationException();

        $this->assertEquals('Unauthorized', $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
    }

    public function testAuthorizationExceptionCreatesWithAbility(): void
    {
        $exception = new AuthorizationException('Not allowed', 'delete', $this);

        $this->assertEquals('Not allowed', $exception->getMessage());
        $this->assertEquals('delete', $exception->getAbility());
        $this->assertSame($this, $exception->getSubject());
    }

    public function testAuthorizationExceptionGeneratesMessageFromAbility(): void
    {
        $exception = new AuthorizationException('Unauthorized', 'edit');

        $this->assertStringContainsString('edit', $exception->getMessage());
    }

    public function testAuthorizationExceptionForAbilityFactory(): void
    {
        $exception = AuthorizationException::forAbility('create', 'Post');

        $this->assertEquals('create', $exception->getAbility());
        $this->assertEquals('Post', $exception->getSubject());
    }
}
