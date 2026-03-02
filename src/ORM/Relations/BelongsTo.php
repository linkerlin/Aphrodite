<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Relations;

use Aphrodite\ORM\Entity;

/**
 * Belongs-to relationship.
 * Example: Post belongs to User (post->user)
 */
class BelongsTo extends Relation
{
    /**
     * Get the related parent entity.
     */
    public function get(): ?Entity
    {
        if ($this->loaded) {
            return $this->result;
        }

        $foreignKeyValue = $this->parent->{$this->foreignKey};

        if ($foreignKeyValue === null) {
            $this->loaded = true;
            $this->result = null;
            return null;
        }

        if ($this->related::isUsingMemoryStore()) {
            $all = $this->related::all();
            foreach ($all as $entity) {
                if ($entity->{$this->localKey} == $foreignKeyValue) {
                    $this->result = $entity;
                    $this->loaded = true;
                    return $this->result;
                }
            }
            $this->loaded = true;
            return null;
        }

        $this->result = $this->related::query()
            ->where($this->localKey, '=', $foreignKeyValue)
            ->first();

        if ($this->result !== null) {
            $this->result = $this->related::fromArray($this->result);
        }

        $this->loaded = true;
        return $this->result;
    }

    /**
     * Associate the parent with a new entity.
     */
    public function associate(Entity $entity): Entity
    {
        $this->parent->{$this->foreignKey} = $entity->{$this->localKey};
        $this->result = $entity;
        $this->loaded = true;
        return $this->parent;
    }

    /**
     * Dissociate the parent from the related entity.
     */
    public function dissociate(): Entity
    {
        $this->parent->{$this->foreignKey} = null;
        $this->result = null;
        $this->loaded = true;
        return $this->parent;
    }
}
