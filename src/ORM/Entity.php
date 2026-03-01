<?php

declare(strict_types=1);

namespace Aphrodite\ORM;

/**
 * Base entity class providing ActiveRecord-style persistence helpers.
 * Concrete subclasses should use PHP attributes or override table/connection
 * methods for real persistence.
 */
abstract class Entity
{
    protected array $attributes = [];

    /** @var array<string, static> In-memory store used when no real DB is configured. */
    private static array $store = [];
    private static int $nextId  = 1;

    /**
     * Return a new query builder / instance for chaining (simplified).
     */
    public static function query(): static
    {
        return new static();
    }

    /**
     * Persist the entity (in-memory implementation).
     */
    public function save(): bool
    {
        if (!isset($this->attributes['id'])) {
            $this->attributes['id'] = self::$nextId++;
        }
        static::$store[$this->attributes['id']] = $this;
        return true;
    }

    /**
     * Return all attributes as an associative array.
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Find an entity by primary key. Returns null when not found.
     */
    public static function find(int $id): ?static
    {
        return static::$store[$id] ?? null;
    }

    /**
     * Return all persisted instances of this class.
     *
     * @return static[]
     */
    public static function all(): array
    {
        return array_values(static::$store);
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }
}
