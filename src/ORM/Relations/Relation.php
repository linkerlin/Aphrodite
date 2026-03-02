<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Relations;

use Aphrodite\ORM\Entity;

/**
 * Abstract base class for entity relations.
 */
abstract class Relation
{
    protected bool $loaded = false;
    protected mixed $result = null;

    public function __construct(
        protected Entity $parent,
        protected string $related,
        protected string $foreignKey,
        protected string $localKey = 'id'
    ) {}

    /**
     * Get the related entity or entities.
     */
    abstract public function get(): mixed;

    /**
     * Check if relation has been loaded.
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Get loaded result without executing query.
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * Load the relation.
     */
    protected function load(): mixed
    {
        if (!$this->loaded) {
            $this->result = $this->get();
            $this->loaded = true;
        }

        return $this->result;
    }

    /**
     * Get the related model class.
     */
    public function getRelated(): string
    {
        return $this->related;
    }

    /**
     * Get the parent entity.
     */
    public function getParent(): Entity
    {
        return $this->parent;
    }
}
