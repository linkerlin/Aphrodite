<?php

declare(strict_types=1);

namespace Aphrodite\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength
{
    public function __construct(public readonly int $length) {}
}
