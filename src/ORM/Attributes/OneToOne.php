<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Attributes;

use Attribute;

/**
 * Attribute for one-to-one relationship.
 * Use when this entity has exactly one of another entity.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToOne
{
    public function __construct(
        public readonly string $targetEntity,
        public readonly string $foreignKey = '',
        public readonly string $localKey = 'id',
    ) {}
}
