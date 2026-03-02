<?php

declare(strict_types=1);

namespace Aphrodite\Tests\DSL;

use Aphrodite\DSL\Parser;
use PHPUnit\Framework\TestCase;

class ParserIntegrationTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testParseFullDsl(): void
    {
        $dsl = <<<DSL
entity User {
    name: string! @unique
    email: string! @verify
    password: string! @hash @min(8)
    age: int
}

api UserApi {
    GET /users
    POST /users
    GET /users/{id}
}

rule ValidationRule {
    if email is unique then allow
}
DSL;

        $result = $this->parser->parse($dsl);

        $this->assertArrayHasKey('entities', $result);
        $this->assertArrayHasKey('apis', $result);
        $this->assertArrayHasKey('rules', $result);
        $this->assertCount(1, $result['entities']);
    }

    public function testParseMultipleEntities(): void
    {
        $dsl = <<<DSL
entity User {
    name: string!
}

entity Post {
    title: string!
    content: text!
}
DSL;

        $result = $this->parser->parse($dsl);

        $this->assertCount(2, $result['entities']);
    }

    public function testParseEntityWithAllFieldTypes(): void
    {
        $dsl = <<<DSL
entity Product {
    id: int!
    name: string!
    price: float
    is_active: bool
    description: text
    data: json
}
DSL;

        $result = $this->parser->parse($dsl);

        $this->assertCount(1, $result['entities']);
        
        $entity = $result['entities'][0];
        $this->assertEquals('Product', $entity['name']);
        $this->assertCount(6, $entity['fields']);
    }

    public function testParseEntityWithDirectives(): void
    {
        $dsl = <<<DSL
entity User {
    email: string! @unique @verify
    password: string! @hash @min(8) @max(100)
}
DSL;

        $result = $this->parser->parse($dsl);

        $entity = $result['entities'][0];
        $emailField = $entity['fields'][0];

        $this->assertContains('unique', $emailField['directives']);
        $this->assertContains('verify', $emailField['directives']);
    }

    public function testParseFieldWithTypeAndRequired(): void
    {
        $dsl = <<<DSL
entity Test {
    field1: string!
    field2: int
    field3
}
DSL;

        $result = $this->parser->parse($dsl);

        $fields = $result['entities'][0]['fields'];

        $this->assertTrue($fields[0]['required']);
        $this->assertFalse($fields[1]['required']);
        $this->assertFalse($fields[2]['required']);
    }

    public function testParseApiBlock(): void
    {
        $dsl = <<<DSL
api UserApi {
    GET /users
    POST /users
    PUT /users/{id}
    DELETE /users/{id}
}
DSL;

        $result = $this->parser->parse($dsl);

        $this->assertCount(1, $result['apis']);
        $this->assertEquals('UserApi', $result['apis'][0]['name']);
    }

    public function testParseRuleBlock(): void
    {
        $dsl = <<<DSL
rule EmailValidation {
    if email is valid then allow
}
DSL;

        $result = $this->parser->parse($dsl);

        $this->assertCount(1, $result['rules']);
        $this->assertEquals('EmailValidation', $result['rules'][0]['name']);
    }

    public function testParseWorkflowBlock(): void
    {
        $dsl = <<<DSL
workflow UserRegistration {
    validate input
    create user
    send welcome email
}
DSL;

        $result = $this->parser->parse($dsl);

        $this->assertCount(1, $result['workflows']);
    }

    public function testParseEmptyEntitiesArray(): void
    {
        $result = $this->parser->parse('');

        $this->assertEmpty($result['entities']);
        $this->assertEmpty($result['apis']);
    }

    public function testParseEntityMethod(): void
    {
        $result = $this->parser->parseEntity('User', 'name: string!');

        $this->assertEquals('User', $result['name']);
        $this->assertCount(1, $result['fields']);
    }

    public function testParseFieldMethod(): void
    {
        // Test field with type and required
        $field = $this->parser->parseField('name: string!');
        
        $this->assertEquals('name', $field['name']);
        $this->assertEquals('string', $field['type']);
        $this->assertTrue($field['required']);

        // Test field without required
        $field2 = $this->parser->parseField('email: string');
        
        $this->assertEquals('email', $field2['name']);
        $this->assertEquals('string', $field2['type']);
        $this->assertFalse($field2['required']);

        // Test field with directives
        $field3 = $this->parser->parseField('email: string! @unique @verify');
        
        $this->assertContains('unique', $field3['directives']);
        $this->assertContains('verify', $field3['directives']);

        // Test bare field name
        $field4 = $this->parser->parseField('status');
        
        $this->assertEquals('status', $field4['name']);
    }
}
