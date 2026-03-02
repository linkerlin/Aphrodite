<?php

declare(strict_types=1);

namespace Aphrodite\Foundation;

use Aphrodite\Container\Container;
use Aphrodite\Container\ContainerInterface;
use Aphrodite\Container\ServiceProviderInterface;

/**
 * Application core class that serves as the main entry point.
 * Extends the container and provides application lifecycle management.
 */
class Application extends Container
{
    /**
     * @var string
     */
    private string $basePath;

    /**
     * @var string
     */
    private string $environment;

    /**
     * @var bool
     */
    private bool $booted = false;

    /**
     * @var array<int, ServiceProviderInterface>
     */
    private array $providers = [];

    /**
     * Create a new application instance.
     *
     * @param string $basePath
     * @param string $environment
     */
    public function __construct(string $basePath, string $environment = 'production')
    {
        $this->basePath = $basePath;
        $this->environment = $environment;

        $this->registerBaseBindings();
    }

    /**
     * Register the base bindings in the container.
     */
    private function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(ContainerInterface::class, $this);
        $this->instance(self::class, $this);
    }

    /**
     * Get the base path of the application.
     *
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the config path.
     *
     * @param string $path
     * @return string
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }

    /**
     * Get the storage path.
     *
     * @param string $path
     * @return string
     */
    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }

    /**
     * Get the current environment.
     *
     * @return string
     */
    public function environment(): string
    {
        return $this->environment;
    }

    /**
     * Check if the application is in local/development environment.
     *
     * @return bool
     */
    public function isLocal(): bool
    {
        return $this->environment === 'local' || $this->environment === 'development';
    }

    /**
     * Check if the application is in production environment.
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * Check if the application is in testing environment.
     *
     * @return bool
     */
    public function isTesting(): bool
    {
        return $this->environment === 'testing';
    }

    /**
     * Register a service provider.
     *
     * @param ServiceProviderInterface $provider
     * @return ServiceProviderInterface
     */
    public function register(ServiceProviderInterface $provider): ServiceProviderInterface
    {
        $provider->register($this);

        $this->providers[] = $provider;

        // If app is already booted, boot the provider immediately
        if ($this->booted) {
            $provider->boot($this);
        }

        return $provider;
    }

    /**
     * Boot the application.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->providers as $provider) {
            $provider->boot($this);
        }

        $this->booted = true;
    }

    /**
     * Check if the application is booted.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Get all registered service providers.
     *
     * @return array<int, ServiceProviderInterface>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Create a new application instance from environment.
     *
     * @param string $basePath
     * @return self
     */
    public static function create(string $basePath): self
    {
        $env = getenv('APP_ENV') ?: 'production';

        return new self($basePath, $env);
    }
}
