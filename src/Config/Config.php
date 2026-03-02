<?php

declare(strict_types=1);

namespace Aphrodite\Config;

/**
 * Configuration manager.
 */
class Config
{
    protected static array $config = [];
    protected static ?string $env = null;

    /**
     * Load configuration from array.
     */
    public static function load(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Load configuration from file.
     */
    public static function loadFile(string $path): void
    {
        if (file_exists($path)) {
            $config = require $path;
            if (is_array($config)) {
                self::load($config);
            }
        }
    }

    /**
     * Get configuration value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value.
     */
    public static function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Check if configuration exists.
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * Get all configuration.
     */
    public static function all(): array
    {
        return self::$config;
    }

    /**
     * Clear configuration.
     */
    public static function clear(): void
    {
        self::$config = [];
    }
}

/**
 * Environment configuration loader.
 */
class Environment
{
    /**
     * Load environment variables from .env file.
     */
    public static function load(string $path = null): void
    {
        $path = $path ?? base_path('.env');

        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                $value = self::parseValue($value);

                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * Parse environment value.
     */
    protected static function parseValue(string $value): mixed
    {
        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if ($value === 'null') {
            return null;
        }

        if (preg_match('/^"(.*)"$/', $value, $m)) {
            return $m[1];
        }

        if (preg_match("/^'(.*)'$/", $value, $m)) {
            return $m[1];
        }

        return $value;
    }

    /**
     * Get environment variable.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return $value;
    }

    /**
     * Check if environment variable exists.
     */
    public static function has(string $key): bool
    {
        return isset($_ENV[$key]) || getenv($key) !== false;
    }

    /**
     * Set environment variable.
     */
    public static function set(string $key, mixed $value): void
    {
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    /**
     * Get current environment.
     */
    public static function getEnv(): string
    {
        return self::get('APP_ENV', 'production');
    }

    /**
     * Check if running in specific environment.
     */
    public static function is(string $env): bool
    {
        return self::getEnv() === $env;
    }

    /**
     * Check if running in development.
     */
    public static function isDevelopment(): bool
    {
        return self::is('development') || self::is('local');
    }

    /**
     * Check if running in production.
     */
    public static function isProduction(): bool
    {
        return self::is('production');
    }
}

/**
 * Get base path.
 */
function base_path(string $path = ''): string
{
    return dirname(__DIR__, 2) . ($path ? DIRECTORY_SEPARATOR . $path : '');
}
