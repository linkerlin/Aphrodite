<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Engine;

use PHPUnit\Framework\TestCase;
use Aphrodite\Engine\SchemaEvolver;

class SchemaEvolverTest extends TestCase
{
    private SchemaEvolver $evolver;

    protected function setUp(): void
    {
        $this->evolver = new SchemaEvolver();
    }

    public function testEvolveDetectsNewFields(): void
    {
        $current = ['fields' => ['id', 'email']];
        $newIntent = ['fields' => ['id', 'email', 'name']];
        $ops = $this->evolver->evolve($current, $newIntent);
        $this->assertNotEmpty($ops);
        $this->assertSame('add_column', $ops[0]['type']);
    }

    public function testDetectBreakingChanges(): void
    {
        $ops = [
            ['type' => 'drop_column', 'field' => 'email'],
            ['type' => 'add_column', 'field' => 'name'],
        ];
        $breaking = $this->evolver->detectBreakingChanges($ops);
        $this->assertCount(1, $breaking);
    }
}
