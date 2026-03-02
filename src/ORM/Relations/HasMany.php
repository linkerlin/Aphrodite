<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Relations;

use Aphrodite\ORM\Entity;

/**
 * Has-many relationship.
 * Example: User has many Posts (user->posts)
 *
 * @extends Relation<int, Entity>
 */
class HasMany extends Relation
{
    protected array $eagerIds = [];

    /**
     * Get all related entities.
     *
     * @return Entity[]
     */
    public function get(): array
    {
        if ($this->loaded) {
            return $this->result;
        }

        $localKeyValue = $this->parent->{$this->localKey};

        if ($this->related::isUsingMemoryStore()) {
            $all = $this->related::all();
            $this->result = array_filter($all, fn($entity) => $entity->{$this->foreignKey} == $localKeyValue);
            $this->result = array_values($this->result);
            $this->loaded = true;
            return $this->result;
        }

        $rows = $this->related::query()
            ->where($this->foreignKey, '=', $localKeyValue)
            ->get();

        $this->result = array_map(
            fn($row) => $this->related::fromArray($row),
            $rows
        );

        $this->loaded = true;
        return $this->result;
    }

    /**
     * Create a new related entity.
     */
    public function create(array $attributes = []): Entity
    {
        $attributes[$this->foreignKey] = $this->parent->{$this->localKey};
        return $this->related::create($attributes);
    }

    /**
     * Save a related entity.
     */
    public function save(Entity $entity): bool
    {
        $entity->{$this->foreignKey} = $this->parent->{$this->localKey};
        return $entity->save();
    }

    /**
     * Attach existing entity to this relation.
     */
    public function attach(Entity $entity): bool
    {
        return $this->save($entity);
    }

    /**
     * Detach entity from this relation.
     */
    public function detach(Entity $entity): bool
    {
        $entity->{$this->foreignKey} = null;
        return $entity->save();
    }

    /**
     * Count related entities.
     */
    public function count(): int
    {
        return count($this->get());
    }

    /**
     * Check if any related entities exist.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }
}
