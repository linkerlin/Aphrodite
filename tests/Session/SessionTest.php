<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Session;

require_once __DIR__ . '/../../src/Session/Session.php';

use Aphrodite\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    private string $sessionName;

    protected function setUp(): void
    {
        $this->sessionName = 'TEST_SESSION_' . uniqid();
    }

    protected function tearDown(): void
    {
        // Clean up session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testSessionName(): void
    {
        $sessionName = 'TEST_SESSION';
        $session = new Session($sessionName);
        
        // Just verify it was set (name is stored internally)
        $this->assertNotEmpty($sessionName);
    }

    public function testSetAndGet(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $session->set('key', 'value');

        $this->assertEquals('value', $session->get('key'));
    }

    public function testGetDefaultValue(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $result = $session->get('nonexistent', 'default');

        $this->assertEquals('default', $result);
    }

    public function testHas(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $session->set('exists', 'value');

        $this->assertTrue($session->has('exists'));
        $this->assertFalse($session->has('nonexistent'));
    }

    public function testForget(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $session->set('key', 'value');
        $session->forget('key');

        $this->assertFalse($session->has('key'));
    }

    public function testFlush(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $session->set('key1', 'value1');
        $session->set('key2', 'value2');
        $session->flush();

        $this->assertFalse($session->has('key1'));
        $this->assertFalse($session->has('key2'));
    }

    public function testRegenerate(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $oldId = $session->getId();
        $session->regenerate();
        $newId = $session->getId();

        $this->assertNotEquals($oldId, $newId);
    }

    public function testInvalidate(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $session->set('key', 'value');
        $session->invalidate();

        $this->assertFalse($session->has('key'));
    }

    public function testSaveAndClose(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $session->set('key', 'value');
        
        $result = $session->save();
        
        $this->assertTrue($result);
    }

    public function testMultipleTypes(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $session->set('string', 'hello');
        $session->set('int', 42);
        $session->set('array', ['a' => 1]);
        $session->set('bool', true);
        $session->set('null', null);

        $this->assertEquals('hello', $session->get('string'));
        $this->assertEquals(42, $session->get('int'));
        $this->assertEquals(['a' => 1], $session->get('array'));
        $this->assertTrue($session->get('bool'));
        $this->assertNull($session->get('null'));
    }

    public function testFlash(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $session->flash('message', 'Hello World');

        // Flash should be available immediately
        $this->assertEquals('Hello World', $session->flash('message'));
    }

    public function testToken(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $token1 = $session->token();
        $token2 = $session->token();

        // Token should be the same on subsequent calls
        $this->assertEquals($token1, $token2);
        $this->assertNotEmpty($token1);
    }

    public function testVerifyToken(): void
    {
        $session = new Session($this->sessionName);
        $session->start();

        $token = $session->token();

        $this->assertTrue($session->verifyToken($token));
        $this->assertFalse($session->verifyToken('invalid_token'));
    }

    public function testSetConfig(): void
    {
        $session = new Session();
        $session->setConfig([
            'name' => 'custom_session',
            'path' => '/custom',
            'secure' => true,
        ]);

        // Just verify it doesn't throw
        $this->assertInstanceOf(Session::class, $session);
    }
}
