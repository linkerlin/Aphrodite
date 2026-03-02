<?php

declare(strict_types=1);

namespace Tests\Engine\Parser;

use Aphrodite\Engine\LLM\MockLLMClient;
use Aphrodite\Engine\Parser\HybridIntentParser;
use Aphrodite\Engine\Parser\Intent;
use Aphrodite\Engine\Parser\RuleBasedParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for IntentParser components.
 */
class IntentParserTest extends TestCase
{
    protected RuleBasedParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RuleBasedParser();
    }

    // === Intent Value Object Tests ===

    #[Test]
    public function intent_can_be_created(): void
    {
        $intent = new Intent(
            entity: 'User',
            features: ['authentication'],
            operations: ['create']
        );

        $this->assertEquals('User', $intent->getEntity());
        $this->assertTrue($intent->hasEntity());
    }

    #[Test]
    public function intent_can_be_created_from_array(): void
    {
        $intent = Intent::fromArray([
            'entity' => 'Product',
            'features' => ['search'],
            'constraints' => ['required' => true],
            'operations' => ['list'],
        ]);

        $this->assertEquals('Product', $intent->getEntity());
        $this->assertEquals(['search'], $intent->getFeatures());
        $this->assertEquals(['required' => true], $intent->getConstraints());
        $this->assertEquals(['list'], $intent->getOperations());
    }

    #[Test]
    public function intent_can_be_empty(): void
    {
        $intent = Intent::empty();

        $this->assertNull($intent->getEntity());
        $this->assertTrue($intent->isEmpty());
        $this->assertFalse($intent->hasEntity());
    }

    #[Test]
    public function intent_can_check_feature(): void
    {
        $intent = new Intent(features: ['authentication', 'email']);

        $this->assertTrue($intent->hasFeature('authentication'));
        $this->assertTrue($intent->hasFeature('email'));
        $this->assertFalse($intent->hasFeature('cache'));
    }

    #[Test]
    public function intent_can_check_operation(): void
    {
        $intent = new Intent(operations: ['create', 'read']);

        $this->assertTrue($intent->hasOperation('create'));
        $this->assertTrue($intent->hasOperation('read'));
        $this->assertFalse($intent->hasOperation('delete'));
    }

    #[Test]
    public function intent_can_check_constraint(): void
    {
        $intent = new Intent(constraints: ['required' => true, 'min_length' => 8]);

        $this->assertTrue($intent->hasConstraint('required'));
        $this->assertTrue($intent->hasConstraint('min_length'));
        $this->assertFalse($intent->hasConstraint('unique'));
    }

    #[Test]
    public function intent_can_get_constraint_value(): void
    {
        $intent = new Intent(constraints: ['min_length' => 8, 'max_length' => 255]);

        $this->assertEquals(8, $intent->getConstraint('min_length'));
        $this->assertEquals(255, $intent->getConstraint('max_length'));
        $this->assertNull($intent->getConstraint('nonexistent'));
        $this->assertEquals('default', $intent->getConstraint('nonexistent', 'default'));
    }

    #[Test]
    public function intent_can_get_metadata(): void
    {
        $intent = new Intent(metadata: ['parser' => 'rule-based', 'confidence' => 0.9]);

        $this->assertEquals('rule-based', $intent->getMeta('parser'));
        $this->assertEquals(0.9, $intent->getMeta('confidence'));
        $this->assertNull($intent->getMeta('nonexistent'));
    }

    #[Test]
    public function intent_can_merge(): void
    {
        $intent1 = new Intent(
            entity: 'User',
            features: ['authentication'],
            operations: ['create']
        );

        $intent2 = new Intent(
            entity: 'Product',
            features: ['search'],
            constraints: ['required' => true],
            operations: ['list']
        );

        $merged = $intent1->merge($intent2);

        $this->assertEquals('Product', $merged->getEntity()); // Second takes precedence
        $this->assertEquals(['authentication', 'search'], $merged->getFeatures());
        $this->assertEquals(['create', 'list'], $merged->getOperations());
        $this->assertEquals(['required' => true], $merged->getConstraints());
    }

    #[Test]
    public function intent_can_add_feature(): void
    {
        $intent = new Intent(features: ['authentication']);
        $newIntent = $intent->withFeature('email');

        $this->assertEquals(['authentication'], $intent->getFeatures()); // Original unchanged
        $this->assertEquals(['authentication', 'email'], $newIntent->getFeatures());
    }

    #[Test]
    public function intent_can_add_operation(): void
    {
        $intent = new Intent(operations: ['create']);
        $newIntent = $intent->withOperation('read');

        $this->assertEquals(['create'], $intent->getOperations());
        $this->assertEquals(['create', 'read'], $newIntent->getOperations());
    }

    #[Test]
    public function intent_can_add_constraint(): void
    {
        $intent = new Intent(constraints: ['required' => true]);
        $newIntent = $intent->withConstraint('min_length', 8);

        $this->assertEquals(['required' => true], $intent->getConstraints());
        $this->assertEquals(['required' => true, 'min_length' => 8], $newIntent->getConstraints());
    }

    #[Test]
    public function intent_can_convert_to_array(): void
    {
        $intent = new Intent(
            entity: 'User',
            features: ['auth'],
            constraints: ['required' => true],
            operations: ['create'],
            metadata: ['parser' => 'test']
        );

        $array = $intent->toArray();

        $this->assertEquals('User', $array['entity']);
        $this->assertEquals(['auth'], $array['features']);
        $this->assertEquals(['required' => true], $array['constraints']);
        $this->assertEquals(['create'], $array['operations']);
        $this->assertEquals(['parser' => 'test'], $array['metadata']);
    }

    // === RuleBasedParser Tests ===

    #[Test]
    public function parser_can_parse_empty_string(): void
    {
        $intent = $this->parser->parse('');

        $this->assertTrue($intent->isEmpty());
    }

    #[Test]
    public function parser_extracts_known_entity(): void
    {
        $intent = $this->parser->parse('Create a user with authentication');

        $this->assertEquals('User', $intent->getEntity());
    }

    #[Test]
    public function parser_extracts_product_entity(): void
    {
        $intent = $this->parser->parse('Manage products with search');

        $this->assertEquals('Product', $intent->getEntity());
    }

    #[Test]
    public function parser_extracts_authentication_feature(): void
    {
        $intent = $this->parser->parse('Build login system with authentication');

        $this->assertTrue($intent->hasFeature('authentication'));
    }

    #[Test]
    public function parser_extracts_email_feature(): void
    {
        $intent = $this->parser->parse('Send email notifications');

        $this->assertTrue($intent->hasFeature('email'));
    }

    #[Test]
    public function parser_extracts_file_upload_feature(): void
    {
        $intent = $this->parser->parse('Allow image upload for users');

        $this->assertTrue($intent->hasFeature('file_upload'));
    }

    #[Test]
    public function parser_extracts_search_feature(): void
    {
        $intent = $this->parser->parse('Add search and filter functionality');

        $this->assertTrue($intent->hasFeature('search'));
    }

    #[Test]
    public function parser_extracts_pagination_feature(): void
    {
        $intent = $this->parser->parse('List items with pagination');

        $this->assertTrue($intent->hasFeature('pagination'));
    }

    #[Test]
    public function parser_extracts_create_operation(): void
    {
        $intent = $this->parser->parse('Create new users');

        $this->assertTrue($intent->hasOperation('create'));
    }

    #[Test]
    public function parser_extracts_list_operation(): void
    {
        $intent = $this->parser->parse('Show all products');

        $this->assertTrue($intent->hasOperation('list'));
    }

    #[Test]
    public function parser_extracts_update_operation(): void
    {
        $intent = $this->parser->parse('Edit and modify user profiles');

        $this->assertTrue($intent->hasOperation('update'));
    }

    #[Test]
    public function parser_extracts_delete_operation(): void
    {
        $intent = $this->parser->parse('Remove and delete comments');

        $this->assertTrue($intent->hasOperation('delete'));
    }

    #[Test]
    public function parser_extracts_required_constraint(): void
    {
        $intent = $this->parser->parse('Email is required and mandatory');

        $this->assertTrue($intent->hasConstraint('required'));
    }

    #[Test]
    public function parser_extracts_unique_constraint(): void
    {
        $intent = $this->parser->parse('Username must be unique');

        $this->assertTrue($intent->hasConstraint('unique'));
    }

    #[Test]
    public function parser_extracts_min_length_constraint(): void
    {
        $intent = $this->parser->parse('Password min length of 8');

        $this->assertEquals(8, $intent->getConstraint('min_length'));
    }

    #[Test]
    public function parser_extracts_max_length_constraint(): void
    {
        $intent = $this->parser->parse('Title maximum length of 100');

        $this->assertEquals(100, $intent->getConstraint('max_length'));
    }

    #[Test]
    public function parser_can_parse_complex_description(): void
    {
        $intent = $this->parser->parse(
            'Create a user registration system with email validation, ' .
            'password min length of 8, authentication, and CRUD operations'
        );

        $this->assertEquals('User', $intent->getEntity());
        $this->assertTrue($intent->hasFeature('authentication'));
        $this->assertTrue($intent->hasFeature('email'));
        $this->assertEquals(8, $intent->getConstraint('min_length'));
        $this->assertTrue($intent->hasOperation('create'));
    }

    #[Test]
    public function parser_can_parse_method(): void
    {
        $this->assertTrue($this->parser->canParse('some text'));
        $this->assertFalse($this->parser->canParse(''));
    }

    #[Test]
    public function parser_returns_name(): void
    {
        $this->assertEquals('rule-based', $this->parser->getName());
    }

    // === HybridIntentParser Tests ===

    #[Test]
    public function hybrid_parser_works_without_llm(): void
    {
        $hybrid = new HybridIntentParser();

        $intent = $hybrid->parse('Create a user with authentication');

        $this->assertEquals('User', $intent->getEntity());
        $this->assertTrue($intent->hasFeature('authentication'));
    }

    #[Test]
    public function hybrid_parser_uses_llm_client(): void
    {
        $llmClient = new MockLLMClient();
        $llmClient->setResponse('product', [
            'entity' => 'Product',
            'features' => ['search'],
            'operations' => ['list'],
        ]);

        $hybrid = new HybridIntentParser($llmClient);

        $intent = $hybrid->parse('Create a product catalog');

        $this->assertEquals('Product', $intent->getEntity());
    }

    #[Test]
    public function hybrid_parser_merges_results(): void
    {
        $llmClient = new MockLLMClient();
        $llmClient->setResponse('special', [
            'features' => ['notification'],
            'constraints' => ['rate_limit' => true],
        ]);

        $hybrid = new HybridIntentParser($llmClient);

        $intent = $hybrid->parse('Create a special user with authentication');

        $this->assertEquals('User', $intent->getEntity()); // From rule parser
        $this->assertTrue($intent->hasFeature('authentication')); // From rule parser
        $this->assertTrue($intent->hasFeature('notification')); // From LLM
    }

    #[Test]
    public function hybrid_parser_can_prefer_llm(): void
    {
        $llmClient = new MockLLMClient();
        $llmClient->setResponse('user', [
            'entity' => 'Member', // LLM says Member, not User
            'features' => ['authentication'],
        ]);

        $hybrid = new HybridIntentParser($llmClient);
        $hybrid->preferLlm();

        $intent = $hybrid->parse('Create a user with authentication');

        $this->assertEquals('Member', $intent->getEntity()); // LLM takes precedence
    }

    #[Test]
    public function hybrid_parser_sets_threshold(): void
    {
        $hybrid = new HybridIntentParser();
        $hybrid->setLlmThreshold(0.5);

        $this->assertInstanceOf(HybridIntentParser::class, $hybrid);
    }

    #[Test]
    public function hybrid_parser_returns_name(): void
    {
        $hybrid = new HybridIntentParser();

        $this->assertEquals('hybrid', $hybrid->getName());
    }

    #[Test]
    public function hybrid_parser_handles_unavailable_llm(): void
    {
        $llmClient = new MockLLMClient();
        $llmClient->setAvailable(false);

        $hybrid = new HybridIntentParser($llmClient);

        $intent = $hybrid->parse('Create a user');

        $this->assertEquals('User', $intent->getEntity()); // Falls back to rule parser
    }
}
