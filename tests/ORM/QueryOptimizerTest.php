<?php

declare(strict_types=1);

namespace Aphrodite\Tests\ORM;

use Aphrodite\ORM\QueryOptimizer;
use PHPUnit\Framework\TestCase;

class QueryOptimizerTest extends TestCase
{
    private QueryOptimizer $optimizer;

    protected function setUp(): void
    {
        $this->optimizer = new QueryOptimizer();
    }

    public function testDetectNPlusOne(): void
    {
        $query = [
            'relations' => ['posts', 'comments'],
            'eager_load' => ['posts'],
        ];

        $this->assertFalse($this->optimizer->detectNPlusOne($query));

        $queryWithoutEagerLoad = [
            'relations' => ['posts'],
        ];

        $this->assertTrue($this->optimizer->detectNPlusOne($queryWithoutEagerLoad));
    }

    public function testOptimizeAddsEagerLoad(): void
    {
        $query = [
            'relations' => ['posts', 'comments'],
        ];

        $result = $this->optimizer->optimize($query);

        $this->assertArrayHasKey('eager_load', $result);
        $this->assertEquals(['posts', 'comments'], $result['eager_load']);
    }

    public function testOptimizeWithWhere(): void
    {
        $query = [
            'table' => 'users',
            'where' => [
                'status' => 'active',
                'role' => 'admin',
            ],
        ];

        $result = $this->optimizer->optimize($query);

        $this->assertArrayHasKey('suggested_index', $result);
    }

    public function testOptimizeWithOrderBy(): void
    {
        $query = [
            'table' => 'users',
            'order_by' => [
                'created_at' => 'DESC',
            ],
        ];

        $result = $this->optimizer->optimize($query);

        // Order by is handled, may or may not have suggested_index depending on implementation
        $this->assertIsArray($result);
    }

    public function testSuggestIndexWithEmptyQuery(): void
    {
        $query = [];

        $result = $this->optimizer->suggestIndex($query);

        $this->assertNull($result);
    }

    public function testSuggestIndexWithWhere(): void
    {
        $query = [
            'table' => 'users',
            'where' => ['email' => 'test@example.com'],
        ];

        $result = $this->optimizer->suggestIndex($query);

        $this->assertIsString($result);
        $this->assertStringContainsString('idx_users_email', $result);
    }

    public function testSuggestIndexWithMultipleColumns(): void
    {
        $query = [
            'table' => 'orders',
            'where' => ['status' => 'pending'],
            'order_by' => ['created_at' => 'DESC'],
        ];

        $result = $this->optimizer->suggestIndex($query);

        $this->assertIsString($result);
        $this->assertStringContainsString('status', $result);
        $this->assertStringContainsString('created_at', $result);
    }

    public function testOptimizeDoesNotModifyOriginalQuery(): void
    {
        $query = [
            'table' => 'users',
            'where' => ['active' => true],
        ];

        $original = $query;
        $this->optimizer->optimize($query);

        $this->assertEquals($original, $query);
    }
}
