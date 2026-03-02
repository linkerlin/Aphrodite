<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Attributes;

use Attribute;

/**
 * Attribute for belongs-to-many relationship (many-to-many).
 * Use when this entity belongs to many of another entity (e.g., User belongs to many Roles).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsToMany
{
    public function __construct(
        public readonly string $targetEntity,
        public readonly ?string $pivotTable = null,
        public readonly ?string $pivotForeignKey = null,
        public readonly ?string $pivotRelatedKey = null,
        public readonly string $localKey = 'id',
        public readonly string $relatedKey = 'id',
    ) {}
}
