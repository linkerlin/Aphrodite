<?php

declare(strict_types=1);

namespace Aphrodite\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class GeneratedValue
{
    public function __construct(public readonly string $strategy = 'AUTO') {}
}
