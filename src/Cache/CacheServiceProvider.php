<?php

declare(strict_types=1);

namespace Aphrodite\Cache;

use Aphrodite\Container\Container;
use Aphrodite\Container\ServiceProviderInterface;

/**
 * Cache service provider.
 */
class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(CacheInterface::class, function (Container $c) {
            $path = $c->has('cache.path')
                ? $c->get('cache.path')
                : null;

            return new FileCache($path);
        });

        $container->singleton('cache', function (Container $c) {
            return $c->get(CacheInterface::class);
        });

        $container->alias(CacheInterface::class, 'cache.driver');
    }

    public function boot(Container $container): void
    {
    }
}
