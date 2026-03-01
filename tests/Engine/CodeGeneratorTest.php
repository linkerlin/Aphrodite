<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Engine;

use PHPUnit\Framework\TestCase;
use Aphrodite\Engine\CodeGenerator;

class CodeGeneratorTest extends TestCase
{
    private CodeGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new CodeGenerator();
    }

    public function testGenerateEntityReturnsString(): void
    {
        $intent = ['entity' => 'Product', 'fields' => [['name' => 'title', 'type' => 'string']]];
        $code = $this->generator->generateEntity($intent);
        $this->assertIsString($code);
    }

    public function testGenerateEntityContainsClassName(): void
    {
        $intent = ['entity' => 'Product', 'fields' => []];
        $code = $this->generator->generateEntity($intent);
        $this->assertStringContainsString('Product', $code);
    }
}
