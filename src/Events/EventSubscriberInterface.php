<?php

declare(strict_types=1);

namespace Aphrodite\Events;

/**
 * Event subscriber interface.
 */
interface EventSubscriberInterface
{
    public function subscribe(EventDispatcher $dispatcher): void;
}
