<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Relations;

use Aphrodite\ORM\Attributes\BelongsTo as BelongsToAttribute;
use Aphrodite\ORM\Attributes\BelongsToMany as BelongsToManyAttribute;
use Aphrodite\ORM\Attributes\HasMany as HasManyAttribute;
use Aphrodite\ORM\Attributes\HasOne as HasOneAttribute;
use Aphrodite\ORM\Attributes\ManyToMany;
use Aphrodite\ORM\Attributes\ManyToOne;
use Aphrodite\ORM\Entity;
use ReflectionClass;
use ReflectionProperty;

/**
 * Trait for loading entity relations via attributes.
 */
trait LoadsRelations
{
    protected array $loadedRelations = [];
    protected array $relationCache = [];

    /**
     * Load relations dynamically.
     */
    public function loadRelation(string $name): mixed
    {
        if (isset($this->loadedRelations[$name])) {
            return $this->relationCache[$name] ?? null;
        }

        $relation = $this->resolveRelation($name);

        if ($relation === null) {
            return null;
        }

        $this->relationCache[$name] = $relation->get();
        $this->loadedRelations[$name] = true;

        return $this->relationCache[$name];
    }

    /**
     * Load multiple relations.
     */
    public function loadRelations(array $relations): static
    {
        foreach ($relations as $name) {
            $this->loadRelation($name);
        }

        return $this;
    }

    /**
     * Get loaded relation result.
     */
    public function getRelation(string $name): mixed
    {
        return $this->relationCache[$name] ?? null;
    }

    /**
     * Check if relation is loaded.
     */
    public function relationLoaded(string $name): bool
    {
        return isset($this->loadedRelations[$name]);
    }

    /**
     * Resolve a relation by name using attributes.
     */
    protected function resolveRelation(string $name): ?Relation
    {
        $reflection = new ReflectionClass($this);

        if (!$reflection->hasProperty($name)) {
            return null;
        }

        $property = $reflection->getProperty($name);
        return $this->createRelationFromAttributes($property);
    }

    /**
     * Create a relation instance from property attributes.
     */
    protected function createRelationFromAttributes(ReflectionProperty $property): ?Relation
    {
        $attributes = $property->getAttributes();

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();

            return match (true) {
                $instance instanceof BelongsToAttribute => $this->createBelongsTo($instance),
                $instance instanceof HasManyAttribute => $this->createHasMany($instance),
                $instance instanceof HasOneAttribute => $this->createHasOne($instance),
                $instance instanceof ManyToOne => $this->createBelongsToFromManyToOne($instance),
                $instance instanceof ManyToMany => $this->createBelongsToManyFromManyToMany($instance),
                default => null,
            };
        }

        return null;
    }

    /**
     * Create BelongsTo relation.
     */
    protected function createBelongsTo(BelongsToAttribute $attribute): BelongsTo
    {
        $foreignKey = $attribute->foreignKey ?: $this->guessBelongsToForeignKey($attribute->targetEntity);

        return new BelongsTo(
            $this,
            $attribute->targetEntity,
            $foreignKey,
            $attribute->ownerKey
        );
    }

    /**
     * Create HasMany relation.
     */
    protected function createHasMany(HasManyAttribute $attribute): HasMany
    {
        $foreignKey = $attribute->foreignKey ?: $this->guessHasManyForeignKey();

        return new HasMany(
            $this,
            $attribute->targetEntity,
            $foreignKey,
            $attribute->localKey
        );
    }

    /**
     * Create HasOne relation.
     */
    protected function createHasOne(HasOneAttribute $attribute): HasOne
    {
        $foreignKey = $attribute->foreignKey ?: $this->guessHasManyForeignKey();

        return new HasOne(
            $this,
            $attribute->targetEntity,
            $foreignKey,
            $attribute->localKey
        );
    }

    /**
     * Create BelongsToMany relation from attribute.
     */
    protected function createBelongsToMany(BelongsToManyAttribute $attribute): BelongsToMany
    {
        return new BelongsToMany(
            $this,
            $attribute->targetEntity,
            table: $attribute->pivotTable ?? '',
            pivotForeignKey: $attribute->pivotForeignKey ?? '',
            pivotRelatedKey: $attribute->pivotRelatedKey ?? '',
            foreignKey: $attribute->relatedKey ?? 'id',
            localKey: $attribute->localKey ?? 'id'
        );
    }

    /**
     * Create BelongsTo from ManyToOne attribute.
     */
    protected function createBelongsToFromManyToOne(ManyToOne $attribute): BelongsTo
    {
        $foreignKey = $this->guessBelongsToForeignKey($attribute->targetEntity);

        return new BelongsTo(
            $this,
            $attribute->targetEntity,
            $foreignKey,
            'id'
        );
    }

    /**
     * Create BelongsToMany from ManyToMany attribute.
     */
    protected function createBelongsToManyFromManyToMany(ManyToMany $attribute): BelongsToMany
    {
        return new BelongsToMany(
            $this,
            $attribute->targetEntity
        );
    }

    /**
     * Guess the foreign key for belongs-to relation.
     */
    protected function guessBelongsToForeignKey(string $targetEntity): string
    {
        $className = basename(str_replace('\\', '/', $targetEntity));
        return strtolower($className) . '_id';
    }

    /**
     * Guess the foreign key for has-many/has-one relation.
     */
    protected function guessHasManyForeignKey(): string
    {
        $className = basename(str_replace('\\', '/', static::class));
        return strtolower($className) . '_id';
    }
}
