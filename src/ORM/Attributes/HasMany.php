<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Attributes;

use Attribute;

/**
 * Attribute for has-many relationship.
 * Use when this entity has many of another entity (e.g., User has many Posts).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HasMany
{
    public function __construct(
        public readonly string $targetEntity,
        public readonly string $foreignKey = '',
        public readonly string $localKey = 'id',
    ) {}
}
