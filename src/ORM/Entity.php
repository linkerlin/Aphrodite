<?php

declare(strict_types=1);

namespace Aphrodite\ORM;

use Aphrodite\Database\Connection;
use Aphrodite\Database\QueryBuilder;
use Aphrodite\ORM\Attributes\Column;
use Aphrodite\ORM\Attributes\Id;
use Aphrodite\ORM\Attributes\GeneratedValue;

/**
 * Base entity class providing ActiveRecord-style persistence with PDO support.
 * Supports both database persistence and in-memory fallback.
 */
abstract class Entity
{
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    /** @var array<string, static> In-memory store used when no real DB is configured. */
    private static array $store = [];
    private static int $nextId = 1;
    private static ?\PDO $pdo = null;
    private static bool $useMemoryStore = true;

    /**
     * Get the table name for this entity.
     */
    public static function getTable(): string
    {
        $class = basename(str_replace('\\', '/', static::class));
        return strtolower($class) . 's';
    }

    /**
     * Get the primary key column name.
     */
    protected static function getPrimaryKey(): string
    {
        return 'id';
    }

    /**
     * Get PDO instance.
     */
    public static function getPdo(): \PDO
    {
        if (self::$pdo === null) {
            self::$pdo = Connection::getInstance();
        }
        return self::$pdo;
    }

    /**
     * Set PDO instance externally.
     */
    public static function setPdo(\PDO $pdo): void
    {
        self::$pdo = $pdo;
        self::$useMemoryStore = false;
    }

    /**
     * Enable/disable memory store mode.
     */
    public static function useMemoryStore(bool $use = true): void
    {
        self::$useMemoryStore = $use;
    }

    /**
     * Check if using memory store.
     */
    public static function isUsingMemoryStore(): bool
    {
        return self::$useMemoryStore;
    }

    /**
     * Create a new query builder for this entity.
     */
    public static function query(): QueryBuilder
    {
        return QueryBuilder::table(static::getPdo(), static::getTable())
            ->setPrimaryKey(static::getPrimaryKey());
    }

    /**
     * Find an entity by primary key.
     */
    public static function find(int $id): ?static
    {
        if (self::$useMemoryStore) {
            return static::$store[static::getTable()][$id] ?? null;
        }

        $row = static::query()->find($id);
        if ($row === null) {
            return null;
        }

        return static::fromArray($row);
    }

    /**
     * Find an entity or fail.
     */
    public static function findOrFail(int $id): static
    {
        $entity = static::find($id);
        if ($entity === null) {
            throw new \RuntimeException(static::class . " with ID {$id} not found");
        }
        return $entity;
    }

    /**
     * Find all entities.
     *
     * @return static[]
     */
    public static function all(): array
    {
        if (self::$useMemoryStore) {
            return array_values(static::$store[static::getTable()] ?? []);
        }

        $rows = static::query()->get();
        return array_map(fn($row) => static::fromArray($row), $rows);
    }

    /**
     * Find entities with conditions.
     *
     * @return static[]
     */
    public static function where(string $column, mixed $operator, mixed $value = null): array
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        if (self::$useMemoryStore) {
            return array_filter(static::$store[static::getTable()] ?? [], function ($entity) use ($column, $operator, $value) {
                return match ($operator) {
                    '=', '==' => $entity->$column == $value,
                    '!=' => $entity->$column != $value,
                    '>' => $entity->$column > $value,
                    '<' => $entity->$column < $value,
                    '>=', '<=', 'like' => true,
                    default => $entity->$column == $value,
                };
            });
        }

        $rows = static::query()->where($column, $operator, $value)->get();
        return array_map(fn($row) => static::fromArray($row), $rows);
    }

    /**
     * Get the first entity matching conditions.
     */
    public static function firstWhere(string $column, mixed $operator, mixed $value = null): ?static
    {
        $results = static::where($column, $operator, $value);
        return $results[0] ?? null;
    }

    /**
     * Create a new entity and save it.
     */
    public static function create(array $attributes): static
    {
        $entity = new static();
        foreach ($attributes as $key => $value) {
            $entity->$key = $value;
        }
        $entity->save();
        return $entity;
    }

    /**
     * Persist the entity to database.
     */
    public function save(): bool
    {
        if (self::$useMemoryStore) {
            return $this->saveToMemory();
        }

        $this->fillTimestamps();

        if ($this->exists) {
            return $this->performUpdate();
        }

        return $this->performInsert();
    }

    /**
     * Fill timestamp fields.
     */
    protected function fillTimestamps(): void
    {
        $now = date('Y-m-d H:i:s');

        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = $now;
        }

        $this->attributes['updated_at'] = $now;
    }

    /**
     * Perform insert operation.
     */
    protected function performInsert(): bool
    {
        $columns = array_keys($this->attributes);
        $values = array_values($this->attributes);

        $id = static::query()->values($this->attributes)->insert();

        if ($id > 0) {
            $pk = static::getPrimaryKey();
            $this->$pk = $id;
            $this->exists = true;
            $this->original = $this->attributes;
            return true;
        }

        return false;
    }

    /**
     * Perform update operation.
     */
    protected function performUpdate(): bool
    {
        $pk = static::getPrimaryKey();
        $affected = static::query()
            ->where($pk, '=', $this->$pk)
            ->set($this->getDirty())
            ->update();

        if ($affected > 0) {
            $this->original = $this->attributes;
            return true;
        }

        return false;
    }

    /**
     * Get dirty (changed) attributes.
     */
    protected function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Save to memory store (fallback).
     */
    private function saveToMemory(): bool
    {
        $table = static::getTable();

        if (!isset(self::$store[$table])) {
            self::$store[$table] = [];
        }

        if (!$this->exists) {
            $pk = static::getPrimaryKey();
            if (!isset($this->attributes[$pk])) {
                $this->attributes[$pk] = self::$nextId++;
            }
            $this->exists = true;
        }

        self::$store[$table][$this->attributes[static::getPrimaryKey()]] = $this;
        $this->original = $this->attributes;
        return true;
    }

    /**
     * Delete the entity.
     */
    public function delete(): bool
    {
        if (self::$useMemoryStore) {
            return $this->deleteFromMemory();
        }

        if (!$this->exists) {
            return false;
        }

        $pk = static::getPrimaryKey();
        $affected = static::query()
            ->where($pk, '=', $this->$pk)
            ->delete();

        if ($affected > 0) {
            $this->exists = false;
            return true;
        }

        return false;
    }

    /**
     * Delete from memory store.
     */
    private function deleteFromMemory(): bool
    {
        $table = static::getTable();
        $pk = static::getPrimaryKey();
        $id = $this->$pk ?? null;

        if ($id !== null && isset(self::$store[$table][$id])) {
            unset(self::$store[$table][$id]);
            $this->exists = false;
            return true;
        }

        return false;
    }

    /**
     * Check if entity was modified.
     */
    public function isDirty(): bool
    {
        return !empty($this->getDirty());
    }

    /**
     * Check if entity exists in database.
     */
    public function isExists(): bool
    {
        return $this->exists;
    }

    /**
     * Fill entity from array.
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

    /**
     * Create entity from array.
     */
    public static function fromArray(array $data): static
    {
        $entity = new static();
        $entity->attributes = $data;
        $entity->original = $data;
        $entity->exists = true;
        return $entity;
    }

    /**
     * Convert entity to array.
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Get attribute value.
     */
    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Set attribute value.
     */
    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Check if attribute is set.
     */
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Unset attribute.
     */
    public function __unset(string $name): void
    {
        unset($this->attributes[$name]);
    }

    /**
     * Reset static state (for testing).
     */
    public static function reset(): void
    {
        self::$store = [];
        self::$nextId = 1;
    }
}
