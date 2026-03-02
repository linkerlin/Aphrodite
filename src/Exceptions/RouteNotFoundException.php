<?php

declare(strict_types=1);

namespace Aphrodite\Exceptions;

/**
 * Exception for route not found errors.
 */
class RouteNotFoundException extends AphroditeException
{
    protected ?string $path = null;
    protected ?string $method = null;

    public function __construct(
        string $path = '',
        string $method = '',
        string $message = '',
        int $code = 404,
        array $context = []
    ) {
        $this->path = $path ?: null;
        $this->method = $method ?: null;

        if ($message === '' && $path !== '') {
            $message = "Route not found: {$method} {$path}";
        }

        parent::__construct($message ?: 'Route not found', $code, null, $context);
    }

    /**
     * Get the path that was not found.
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Get the HTTP method.
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Create for a specific path and method.
     */
    public static function forPath(string $path, string $method = 'GET'): self
    {
        return new self($path, $method);
    }
}
