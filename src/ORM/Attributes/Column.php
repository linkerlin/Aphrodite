<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Attributes;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly bool $nullable = false,
        public readonly ?int $length = null,
        public readonly mixed $enum = null,
    ) {}
}
