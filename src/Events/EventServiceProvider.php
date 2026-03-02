<?php

declare(strict_types=1);

namespace Aphrodite\Events;

use Aphrodite\Container\Container;
use Aphrodite\Container\ServiceProviderInterface;

/**
 * Event service provider.
 */
class EventServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(EventDispatcher::class, function () {
            return new EventDispatcher();
        });

        $container->singleton('events', function (Container $c) {
            return $c->get(EventDispatcher::class);
        });

        $container->alias(EventDispatcher::class, 'event.dispatcher');
    }

    public function boot(Container $container): void
    {
    }
}
