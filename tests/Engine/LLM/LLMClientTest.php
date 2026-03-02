<?php

declare(strict_types=1);

namespace Tests\Engine\LLM;

use Aphrodite\Engine\LLM\MockLLMClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for LLM client.
 */
class LLMClientTest extends TestCase
{
    protected MockLLMClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new MockLLMClient();
    }

    #[Test]
    public function client_is_available_by_default(): void
    {
        $this->assertTrue($this->client->isAvailable());
    }

    #[Test]
    public function client_can_be_set_unavailable(): void
    {
        $this->client->setAvailable(false);

        $this->assertFalse($this->client->isAvailable());
    }

    #[Test]
    public function client_returns_model_name(): void
    {
        $this->assertEquals('mock-llm-v1', $this->client->getModelName());
    }

    #[Test]
    public function client_parses_intent_with_predefined_response(): void
    {
        $this->client->setResponse('user', [
            'entity' => 'User',
            'features' => ['authentication'],
            'operations' => ['create'],
        ]);

        $result = $this->client->parseIntent('Create a user system');

        $this->assertEquals('User', $result['entity']);
        $this->assertEquals(['authentication'], $result['features']);
        $this->assertEquals(['create'], $result['operations']);
    }

    #[Test]
    public function client_parses_intent_with_default_response(): void
    {
        $result = $this->client->parseIntent('Create a user with authentication');

        $this->assertEquals('User', $result['entity']);
        $this->assertContains('authentication', $result['features']);
    }

    #[Test]
    public function client_detects_product_entity(): void
    {
        $result = $this->client->parseIntent('Manage product catalog');

        $this->assertEquals('Product', $result['entity']);
    }

    #[Test]
    public function client_detects_order_entity(): void
    {
        $result = $this->client->parseIntent('Process orders');

        $this->assertEquals('Order', $result['entity']);
    }

    #[Test]
    public function client_detects_create_operation(): void
    {
        $result = $this->client->parseIntent('Create new items');

        $this->assertContains('create', $result['operations']);
    }

    #[Test]
    public function client_detects_list_operation(): void
    {
        $result = $this->client->parseIntent('Show all records');

        $this->assertContains('list', $result['operations']);
    }

    #[Test]
    public function client_detects_delete_operation(): void
    {
        $result = $this->client->parseIntent('Remove unwanted items');

        $this->assertContains('delete', $result['operations']);
    }

    #[Test]
    public function client_detects_email_feature(): void
    {
        $result = $this->client->parseIntent('Send email notifications');

        $this->assertContains('email', $result['features']);
    }

    #[Test]
    public function client_detects_file_upload_feature(): void
    {
        $result = $this->client->parseIntent('Allow file upload');

        $this->assertContains('file_upload', $result['features']);
    }

    #[Test]
    public function client_returns_empty_when_unavailable(): void
    {
        $this->client->setAvailable(false);

        $result = $this->client->parseIntent('Create a user');

        $this->assertEmpty($result);
        $this->assertEquals('Client not available', $this->client->getLastError());
    }

    #[Test]
    public function client_generates_code(): void
    {
        $code = $this->client->generateCode('Create a User class');

        $this->assertStringContainsString('Generated code', $code);
        $this->assertStringContainsString('Create a User class', $code);
    }

    #[Test]
    public function client_completes_code(): void
    {
        $code = $this->client->completeCode('class User {');

        $this->assertStringContainsString('class User {', $code);
        $this->assertStringContainsString('Mock completion', $code);
    }

    #[Test]
    public function client_returns_empty_code_when_unavailable(): void
    {
        $this->client->setAvailable(false);

        $code = $this->client->generateCode('test');

        $this->assertEquals('', $code);
    }

    #[Test]
    public function client_has_no_error_initially(): void
    {
        $this->assertNull($this->client->getLastError());
    }

    #[Test]
    public function client_pattern_matching_is_case_insensitive(): void
    {
        $this->client->setResponse('USER', [
            'entity' => 'CustomUser',
        ]);

        $result = $this->client->parseIntent('create a user');

        $this->assertEquals('CustomUser', $result['entity']);
    }
}
