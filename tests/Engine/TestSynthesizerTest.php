<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Engine;

use PHPUnit\Framework\TestCase;
use Aphrodite\Engine\TestSynthesizer;

class TestSynthesizerTest extends TestCase
{
    private TestSynthesizer $synthesizer;

    protected function setUp(): void
    {
        $this->synthesizer = new TestSynthesizer();
    }

    public function testSynthesizeReturnsString(): void
    {
        $intent = ['entity' => 'User', 'fields' => []];
        $code = $this->synthesizer->synthesize($intent);
        $this->assertIsString($code);
    }

    public function testSynthesizeContainsTestClass(): void
    {
        $intent = ['entity' => 'User', 'fields' => []];
        $code = $this->synthesizer->synthesize($intent);
        $this->assertStringContainsString('Test', $code);
    }
}
