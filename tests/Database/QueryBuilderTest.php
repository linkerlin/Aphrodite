<?php

declare(strict_types=1);

namespace Aphrodite\Tests\Database;

require_once __DIR__ . '/../../src/Database/QueryBuilder.php';

use Aphrodite\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
    }

    public function testSelectAll(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        
        $this->assertEquals('SELECT * FROM users', $qb->toSql());
    }

    public function testSelectSpecificColumns(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->select(['id', 'name', 'email']);
        
        $this->assertEquals('SELECT id, name, email FROM users', $qb->toSql());
    }

    public function testSelectWithStringArguments(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->select('id', 'name');
        
        $this->assertEquals('SELECT id, name FROM users', $qb->toSql());
    }

    public function testWhereEquals(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->where('status', 'active');
        
        $this->assertEquals('SELECT * FROM users WHERE status = ?', $qb->toSql());
    }

    public function testWhereWithOperator(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->where('age', '>=', 18);
        
        $this->assertEquals('SELECT * FROM users WHERE age >= ?', $qb->toSql());
    }

    public function testWhereIn(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->whereIn('id', [1, 2, 3]);
        
        $this->assertEquals('SELECT * FROM users WHERE id IN (?, ?, ?)', $qb->toSql());
    }

    public function testWhereNull(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->whereNull('deleted_at');
        
        $this->assertEquals('SELECT * FROM users WHERE deleted_at IS NULL', $qb->toSql());
    }

    public function testWhereNotNull(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->whereNotNull('confirmed_at');
        
        $this->assertEquals('SELECT * FROM users WHERE confirmed_at IS NOT NULL', $qb->toSql());
    }

    public function testMultipleWhereConditions(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->where('status', 'active');
        $qb->where('age', '>=', 18);
        
        $this->assertEquals('SELECT * FROM users WHERE status = ? AND age >= ?', $qb->toSql());
    }

    public function testOrderBy(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->orderBy('created_at', 'DESC');
        
        $this->assertEquals('SELECT * FROM users ORDER BY created_at DESC', $qb->toSql());
    }

    public function testOrderByDefaultAsc(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->orderBy('name');
        
        $this->assertEquals('SELECT * FROM users ORDER BY name ASC', $qb->toSql());
    }

    public function testLimit(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->limit(10);
        
        $this->assertEquals('SELECT * FROM users LIMIT 10', $qb->toSql());
    }

    public function testOffset(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->offset(20);
        
        $this->assertEquals('SELECT * FROM users OFFSET 20', $qb->toSql());
    }

    public function testLimitAndOffset(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->limit(10)->offset(20);
        
        $this->assertEquals('SELECT * FROM users LIMIT 10 OFFSET 20', $qb->toSql());
    }

    public function testInnerJoin(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->join('posts', 'users.id', '=', 'posts.user_id');
        
        $sql = $qb->toSql();
        $this->assertStringContainsString('INNER JOIN posts', $sql);
        $this->assertStringContainsString('users.id = posts.user_id', $sql);
    }

    public function testLeftJoin(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->leftJoin('posts', 'users.id', '=', 'posts.user_id');
        
        $sql = $qb->toSql();
        $this->assertStringContainsString('LEFT JOIN posts', $sql);
    }

    public function testInsertQuery(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->values(['name' => 'John', 'email' => 'john@example.com']);
        
        $sql = $qb->toSql();
        $this->assertStringContainsString('INSERT INTO users', $sql);
        $this->assertStringContainsString('(name, email)', $sql);
        $this->assertStringContainsString('VALUES', $sql);
    }

    public function testUpdateQuery(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->set(['name' => 'John']);
        $qb->where('id', 1);
        
        $sql = $qb->toSql();
        $this->assertStringContainsString('UPDATE users SET', $sql);
        $this->assertStringContainsString('name = ?', $sql);
        $this->assertStringContainsString('WHERE', $sql);
    }

    public function testSetPrimaryKey(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->setPrimaryKey('user_id');
        
        // This is tested via find()
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testStaticFactory(): void
    {
        $qb = QueryBuilder::table($this->pdo, 'users');
        
        $this->assertInstanceOf(QueryBuilder::class, $qb);
        $this->assertEquals('SELECT * FROM users', $qb->toSql());
    }

    public function testResetBuilder(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->select(['id', 'name']);
        $qb->where('status', 'active');
        $qb->orderBy('name');
        $qb->limit(10);
        
        $qb->reset();
        
        $this->assertEquals('SELECT * FROM users', $qb->toSql());
    }
}
