<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToOne
{
    public function __construct(
        public readonly string $targetEntity,
        public readonly string $inversedBy = '',
    ) {}
}
