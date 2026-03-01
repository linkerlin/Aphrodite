<?php

declare(strict_types=1);

namespace Aphrodite\Tests\DSL;

use PHPUnit\Framework\TestCase;
use Aphrodite\DSL\Parser;

class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testParseEntity(): void
    {
        $dsl = "entity User {\n    email: string!\n}";
        $result = $this->parser->parse($dsl);
        $this->assertArrayHasKey('entities', $result);
        $this->assertNotEmpty($result['entities']);
    }

    public function testParseEntityFields(): void
    {
        $result = $this->parser->parseEntity('User', "email: string!\nname: string");
        $this->assertArrayHasKey('fields', $result);
        $this->assertCount(2, $result['fields']);
    }

    public function testParseFullDsl(): void
    {
        $dsl = "entity User {\n    email: string! @unique\n    password: string! @min(8)\n}";
        $result = $this->parser->parse($dsl);
        $this->assertArrayHasKey('entities', $result);
        $this->assertCount(1, $result['entities']);
    }
}
