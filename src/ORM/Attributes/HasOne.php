<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Attributes;

use Attribute;

/**
 * Attribute for has-one relationship.
 * Use when this entity has exactly one of another entity (e.g., User has one Profile).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HasOne
{
    public function __construct(
        public readonly string $targetEntity,
        public readonly string $foreignKey = '',
        public readonly string $localKey = 'id',
    ) {}
}
