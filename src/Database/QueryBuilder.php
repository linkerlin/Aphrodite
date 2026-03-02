<?php

declare(strict_types=1);

namespace Aphrodite\Database;

/**
 * Query Builder for fluent SQL query construction.
 */
class QueryBuilder
{
    private \PDO $pdo;
    private string $table;
    private string $primaryKey = 'id';

    private array $select = ['*'];
    private array $joins = [];
    private array $where = [];
    private array $whereBindings = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $values = [];
    private array $set = [];

    private string $type = 'select';

    public function __construct(\PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Create a new query builder for a table.
     */
    public static function table(\PDO $pdo, string $table): self
    {
        return new self($pdo, $table);
    }

    /**
     * Set the primary key column.
     */
    public function setPrimaryKey(string $key): self
    {
        $this->primaryKey = $key;
        return $this;
    }

    /**
     * Specify columns to select.
     */
    public function select(array|string $columns): self
    {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a join clause.
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    /**
     * Add a left join clause.
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = "LEFT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    /**
     * Add a where condition.
     */
    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $conjunction = empty($this->where) ? '' : 'AND ';
        $this->where[] = "{$conjunction}{$column} {$operator} ?";
        $this->whereBindings[] = $value;
        return $this;
    }

    /**
     * Add an OR where condition.
     */
    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = "OR {$column} {$operator} ?";
        $this->whereBindings[] = $value;
        return $this;
    }

    /**
     * Add a where in condition.
     */
    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->where[] = "{$column} IN ({$placeholders})";
        $this->whereBindings = array_merge($this->whereBindings, $values);
        return $this;
    }

    /**
     * Add a where null condition.
     */
    public function whereNull(string $column): self
    {
        $this->where[] = "{$column} IS NULL";
        return $this;
    }

    /**
     * Add a where not null condition.
     */
    public function whereNotNull(string $column): self
    {
        $this->where[] = "{$column} IS NOT NULL";
        return $this;
    }

    /**
     * Add an order by clause.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "{$column} " . strtoupper($direction);
        return $this;
    }

    /**
     * Set the limit.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the offset.
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set values for insert.
     */
    public function values(array $values): self
    {
        $this->values = $values;
        $this->type = 'insert';
        return $this;
    }

    /**
     * Set values for update.
     */
    public function set(array $values): self
    {
        foreach ($values as $key => $value) {
            $this->set[] = "{$key} = ?";
            $this->whereBindings[] = $value;
        }
        $this->type = 'update';
        return $this;
    }

    /**
     * Execute the query and get all results.
     */
    public function get(): array
    {
        $sql = $this->buildSelect();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->whereBindings);
        return $stmt->fetchAll();
    }

    /**
     * Execute the query and get the first result.
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Find a record by primary key.
     */
    public function find(int $id): ?array
    {
        return $this->where($this->primaryKey, '=', $id)->first();
    }

    /**
     * Insert a record and return the last insert ID.
     */
    public function insert(): int
    {
        $columns = implode(', ', array_keys($this->values));
        $placeholders = implode(', ', array_fill(0, count($this->values), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($this->values));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update records and return affected row count.
     */
    public function update(): int
    {
        $sql = "UPDATE {$this->table} SET " . implode(', ', $this->set);

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' ', $this->where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->whereBindings);

        return $stmt->rowCount();
    }

    /**
     * Delete records and return affected row count.
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' ', $this->where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->whereBindings);

        return $stmt->rowCount();
    }

    /**
     * Count records.
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' ', $this->where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->whereBindings);
        $result = $stmt->fetch();

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Check if records exist.
     */
    public function exists(): bool
    {
        $this->limit(1);
        return $this->first() !== null;
    }

    /**
     * Get the raw SQL string (for debugging).
     */
    public function toSql(): string
    {
        return match ($this->type) {
            'insert' => $this->buildInsert(),
            'update' => $this->buildUpdate(),
            default => $this->buildSelect(),
        };
    }

    /**
     * Build select SQL.
     */
    private function buildSelect(): string
    {
        $sql = "SELECT " . implode(', ', $this->select);
        $sql .= " FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' ', $this->where);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Build insert SQL.
     */
    private function buildInsert(): string
    {
        $columns = implode(', ', array_keys($this->values));
        $placeholders = implode(', ', array_fill(0, count($this->values), '?'));

        return "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
    }

    /**
     * Build update SQL.
     */
    private function buildUpdate(): string
    {
        $sql = "UPDATE {$this->table} SET " . implode(', ', $this->set);

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' ', $this->where);
        }

        return $sql;
    }

    /**
     * Reset the query builder state.
     */
    public function reset(): self
    {
        $this->select = ['*'];
        $this->joins = [];
        $this->where = [];
        $this->whereBindings = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->values = [];
        $this->set = [];
        $this->type = 'select';

        return $this;
    }
}
