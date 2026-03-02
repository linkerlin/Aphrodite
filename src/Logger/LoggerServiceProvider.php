<?php

declare(strict_types=1);

namespace Aphrodite\Logger;

use Aphrodite\Container\Container;
use Aphrodite\Container\ServiceProviderInterface;

/**
 * Logger service provider.
 */
class LoggerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(LoggerInterface::class, function (Container $c) {
            if ($c->has('log.path')) {
                $path = $c->get('log.path');
            } elseif ($c->has('app')) {
                $path = $c->get('app')->storagePath('logs/aphrodite.log');
            } else {
                $path = sys_get_temp_dir() . '/aphrodite.log';
            }

            $minLevel = $c->has('log.level')
                ? Level::priority($c->get('log.level'))
                : null;

            return new FileLogger($path, $minLevel);
        });

        $container->singleton('logger', function (Container $container) {
            return $container->get(LoggerInterface::class);
        });

        $container->alias(LoggerInterface::class, 'log');
    }

    public function boot(Container $container): void
    {
    }
}
