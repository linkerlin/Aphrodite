<?php

declare(strict_types=1);

namespace Aphrodite\Queue;

use Aphrodite\Container\Container;
use Aphrodite\Container\ServiceProviderInterface;

/**
 * Queue service provider.
 */
class QueueServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(QueueInterface::class, function (Container $c) {
            $path = $c->has('queue.path')
                ? $c->get('queue.path')
                : null;

            if ($path !== null) {
                return new FileQueue($path);
            }

            return new ArrayQueue();
        });

        $container->singleton('queue', function (Container $c) {
            return $c->get(QueueInterface::class);
        });

        $container->alias(QueueInterface::class, 'queue.driver');
    }

    public function boot(Container $container): void
    {
    }
}
