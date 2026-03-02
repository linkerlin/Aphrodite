<?php

declare(strict_types=1);

namespace Aphrodite\Container;

/**
 * Service provider interface for deferred service registration.
 */
interface ServiceProviderInterface
{
    /**
     * Register services with the container.
     */
    public function register(Container $container): void;

    /**
     * Bootstrap services after all providers are registered.
     */
    public function boot(Container $container): void;
}
