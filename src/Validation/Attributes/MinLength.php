<?php

declare(strict_types=1);

namespace Aphrodite\Validation\Attributes;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MinLength
{
    public function __construct(public readonly int $length) {}
}
