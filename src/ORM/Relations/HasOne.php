<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Relations;

use Aphrodite\ORM\Entity;

/**
 * Has-one relationship.
 * Example: User has one Profile (user->profile)
 */
class HasOne extends Relation
{
    /**
     * Get the related entity.
     */
    public function get(): ?Entity
    {
        if ($this->loaded) {
            return $this->result;
        }

        $localKeyValue = $this->parent->{$this->localKey};

        if ($this->related::isUsingMemoryStore()) {
            $all = $this->related::all();
            foreach ($all as $entity) {
                if ($entity->{$this->foreignKey} == $localKeyValue) {
                    $this->result = $entity;
                    $this->loaded = true;
                    return $this->result;
                }
            }
            $this->loaded = true;
            return null;
        }

        $row = $this->related::query()
            ->where($this->foreignKey, '=', $localKeyValue)
            ->first();

        if ($row !== null) {
            $this->result = $this->related::fromArray($row);
        }

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
     * Delete the related entity.
     */
    public function delete(): bool
    {
        $related = $this->get();

        if ($related === null) {
            return false;
        }

        return $related->delete();
    }
}
