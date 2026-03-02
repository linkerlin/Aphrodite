<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Attributes;

use Attribute;

/**
 * Attribute for belongs-to relationship.
 * Use when this entity belongs to another entity (e.g., Post belongs to User).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo
{
    public function __construct(
        public readonly string $targetEntity,
        public readonly string $foreignKey = '',
        public readonly string $ownerKey = 'id',
    ) {}
}
