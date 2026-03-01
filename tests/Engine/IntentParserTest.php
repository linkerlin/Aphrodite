<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Engine;

use PHPUnit\Framework\TestCase;
use Aphrodite\Engine\IntentParser;

class IntentParserTest extends TestCase
{
    private IntentParser $parser;

    protected function setUp(): void
    {
        $this->parser = new IntentParser();
    }

    public function testParseReturnsArray(): void
    {
        $result = $this->parser->parse('create a user system');
        $this->assertIsArray($result);
    }

    public function testParseUserRegistration(): void
    {
        $result = $this->parser->parse('create a user system with email registration');
        $this->assertArrayHasKey('entity', $result);
        $this->assertArrayHasKey('features', $result);
        $this->assertArrayHasKey('operations', $result);
    }

    public function testParseEmptyString(): void
    {
        $result = $this->parser->parse('');
        $this->assertIsArray($result);
    }
}
