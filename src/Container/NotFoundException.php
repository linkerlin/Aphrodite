<?php

declare(strict_types=1);

namespace Aphrodite\Container;

use Exception;

/**
 * Service not found exception.
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
